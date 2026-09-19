# Deployment Runbook

Moving this app to a VPS. Follow in order. If you're deploying onto the same
VPS that already runs gasflow (gas-distribution), **skip Phase 1 and Phase 3**
— Docker and the shared Traefik instance are already set up there; just reuse
the existing `proxy` network.

## Phase 1 — New server prep

```bash
apt update && apt upgrade -y
apt install -y ufw git
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable

curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh
rm get-docker.sh
usermod -aG docker $USER    # then log out/in, or `newgrp docker`
```

## Phase 2 — MySQL (host-installed, not containerized)

```bash
apt install -y mysql-server
mysql_secure_installation
```

```sql
CREATE DATABASE `claude_ecom` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'appuser'@'%' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON `claude_ecom`.* TO 'appuser'@'%';
FLUSH PRIVILEGES;
```

Make MySQL reachable from Docker containers — two required changes:

```bash
sudo sed -i 's/bind-address\s*=\s*127.0.0.1/bind-address = 0.0.0.0/' /etc/mysql/mysql.conf.d/mysqld.cnf
sudo systemctl restart mysql
sudo ufw allow from 172.16.0.0/12 to any port 3306 proto tcp
```

Verify:
```bash
sudo ss -tlnp | grep 3306      # must show 0.0.0.0:3306, not 127.0.0.1:3306
sudo ufw status                 # must show 3306/tcp ALLOW 172.16.0.0/12
```

(If this VPS already runs gasflow, its MySQL is already reachable this way —
just create the `claude_ecom` database and user above on the existing server.)

## Phase 3 — Traefik (reverse proxy + TLS)

```bash
mkdir -p ~/traefik/letsencrypt
docker network create proxy
```

`~/traefik/docker-compose.yml`:
```yaml
services:
  traefik:
    image: traefik:v3.7
    command:
      - --providers.docker=true
      - --providers.docker.exposedbydefault=false
      - --entrypoints.web.address=:80
      - --entrypoints.websecure.address=:443
      - --entrypoints.web.http.redirections.entrypoint.to=websecure
      - --entrypoints.web.http.redirections.entrypoint.scheme=https
      - --certificatesresolvers.le.acme.email=YOUR_EMAIL
      - --certificatesresolvers.le.acme.storage=/letsencrypt/acme.json
      - --certificatesresolvers.le.acme.httpchallenge.entrypoint=web
    ports: ["80:80", "443:443"]
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - ./letsencrypt:/letsencrypt
    networks: [proxy]
networks:
  proxy:
    external: true
```
```bash
cd ~/traefik && docker compose up -d
```

## Phase 4 — DNS

Add A records for both hostnames pointing at the VPS IP:
- `ecom.YOURDOMAIN.tld` → VPS_IP
- `admin.ecom.YOURDOMAIN.tld` → VPS_IP

Set both to **DNS only** (not proxied) if using Cloudflare — proxied mode
breaks Let's Encrypt's HTTP-01 challenge. Also check there's no leftover
**AAAA** record on either hostname pointing elsewhere.

Once you know the real hostnames, put them in `docker/docker-compose.prod.yml`
in place of `REPLACE_WITH_PROD_DOMAIN` and `REPLACE_WITH_PROD_ADMIN_DOMAIN`.

## Phase 5 — Clone repo + configure

```bash
git clone git@github-personal:jubaerhossainece/claude-ecom.git ~/claude-ecom
cd ~/claude-ecom
cp .env.docker.example .env.docker
```

Edit `.env.docker` — every one of these must be changed from the template default:
```
APP_URL=https://ecom.YOURDOMAIN.tld
ADMIN_DOMAIN=admin.ecom.YOURDOMAIN.tld
DB_DATABASE=claude_ecom          # must match the DB actually created in Phase 2
DB_USERNAME=appuser              # must match the user actually created in Phase 2
DB_PASSWORD=STRONG_PASSWORD_HERE
```
Leave `APP_KEY=` blank — it's filled in automatically on first deploy from the
`APP_KEY` GitHub secret (Phase 6). Add `RUN_MIGRATIONS=true` to apply pending
migrations on boot (never `migrate:fresh`).

(`DB_HOST` is force-overridden to `host.docker.internal` by `docker-compose.yml`
regardless of this file — leave it as-is.)

## Phase 6 — Generate APP_KEY + SSH key for GitHub Actions

Generate the app key once, locally — no container needed:
```bash
docker run --rm php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```
Save that value for the `APP_KEY` secret below. `.github/workflows/deploy.yml`
writes it into `.env.docker` on the VPS on every deploy, so there's no manual
`artisan key:generate` + paste-into-running-container step.

```bash
ssh-keygen -t ed25519 -f ~/.ssh/gh_deploy_key -N ""
cat ~/.ssh/gh_deploy_key.pub >> ~/.ssh/authorized_keys    # lets GitHub Actions log INTO this VPS
cat ~/.ssh/gh_deploy_key                                   # copy this — goes into a GitHub secret below
```
If this repo is private and hasn't been cloned here before, also add
`~/.ssh/gh_deploy_key.pub` to your GitHub account's SSH keys (or a repo
deploy key) so `git clone`/`git pull` works from this VPS.

## Phase 7 — GitHub repo secrets

`https://github.com/jubaerhossainece/claude-ecom/settings/secrets/actions` → add:

| Secret | Value |
|---|---|
| `APP_KEY` | value generated in Phase 6 |
| `VPS_HOST` | VPS's IP |
| `VPS_USER` | your VPS username |
| `VPS_SSH_KEY` | private key from Phase 6 (full `-----BEGIN...-----END-----` block) |
| `VPS_DEPLOY_PATH` | `/home/YOUR_USER/claude-ecom` |

## Phase 8 — First deploy

Push any commit (or re-run the workflow manually from the Actions tab). Watch
`https://github.com/jubaerhossainece/claude-ecom/actions`.

Check containers came up:
```bash
docker ps -a
```
Should show `app`, `queue`, `nginx` all `Up` (plus `traefik` from Phase 3, if
not already running for another app on this host).

## Phase 9 — Migrate + seed (first deploy only)

If `RUN_MIGRATIONS=true` was set in Phase 5, migrations already ran on boot.
Otherwise:
```bash
docker exec claude-ecom-app-1 php artisan migrate --force
docker exec claude-ecom-app-1 php artisan migrate:status    # sanity check
```

Seed the store + geo data:
```bash
docker exec claude-ecom-app-1 php artisan db:seed --force
```

Change the seeded admin password immediately — it defaults to
`admin@store.test` / `password`:
```bash
docker exec -it claude-ecom-app-1 php artisan tinker
```
```php
$a = App\Models\User::where('email', 'admin@store.test')->first();
$a->update(['password' => bcrypt('something-long-and-random')]);
exit
```

## Phase 10 — Verify

- `https://ecom.YOURDOMAIN.tld` — storefront loads, real TLS padlock, styled
- `https://admin.ecom.YOURDOMAIN.tld` — Filament admin login (its own subdomain, not `/admin` — see `ADMIN_DOMAIN`)
- `docker logs claude-ecom-app-1 --tail 30` — no repeating DB-timeout errors
- `docker logs claude-ecom-queue-1 --tail 30` — same check
- `docker logs claude-ecom-nginx-1 --tail 30` — no `502`s

---

## Quick fixes for errors likely to come up (per gasflow's experience)

| Symptom | Cause | Fix |
|---|---|---|
| GitHub Actions: `error: missing server host` | `VPS_HOST` secret not set | Add the secrets in Phase 7 |
| GitHub Actions: `ssh: no key found` | `VPS_SSH_KEY` secret has bad/partial content | Re-copy full private key, including `BEGIN`/`END` lines |
| GitHub Actions: `fatal: not a git repository` | `VPS_DEPLOY_PATH` wrong/unset, or repo not cloned there yet | Confirm path matches Phase 5's clone location exactly |
| Browser can't reach domain at all | DNS proxied through Cloudflare, or stray AAAA record | Set DNS-only (Phase 4), remove AAAA records |
| `502 Bad Gateway` from nginx | `app` container never started PHP-FPM (usually stuck in DB wait loop) | Check `docker logs claude-ecom-app-1` |
| `SQLSTATE[HY000] [2002] Operation timed out` | MySQL `bind-address` still `127.0.0.1`, and/or `ufw` blocking port 3306 from Docker's network | Phase 2's `sed`/`ufw allow` commands |
| `MissingAppKeyException` | `APP_KEY` secret not set, or `.env.docker` never got created on the VPS from the example | Confirm Phase 5's `cp` and Phase 7's `APP_KEY` secret |
| Containers running but env changes not applied | `docker compose restart` doesn't re-read `.env.docker` | Use `docker compose -f docker/docker-compose.yml -f docker/docker-compose.prod.yml --env-file .env.docker up -d` (recreates, not just restarts) |
