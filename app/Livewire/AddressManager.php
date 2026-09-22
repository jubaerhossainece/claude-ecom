<?php

namespace App\Livewire;

use App\Models\District;
use App\Models\Division;
use App\Models\Thana;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AddressManager extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $label = 'Home';

    public string $name = '';

    public string $phone = '';

    public ?int $division_id = null;

    public ?int $district_id = null;

    public ?int $thana_id = null;

    public string $area = '';

    public string $address_line = '';

    public bool $is_default = false;

    protected function rules(): array
    {
        return [
            'label' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'division_id' => 'required|exists:divisions,id',
            'district_id' => 'required|exists:districts,id',
            'thana_id' => 'required|exists:thanas,id',
            'area' => 'nullable|string|max:255',
            'address_line' => 'required|string|max:500',
        ];
    }

    public function getAddressesProperty()
    {
        return Auth::guard('customer')->user()->addresses()->orderByDesc('is_default')->latest()->get();
    }

    public function getDivisionsProperty()
    {
        return Division::orderBy('name')->get();
    }

    public function getDistrictsProperty()
    {
        return $this->division_id
            ? District::where('division_id', $this->division_id)->orderBy('name')->get()
            : collect();
    }

    public function getThanasProperty()
    {
        return $this->district_id
            ? Thana::where('district_id', $this->district_id)->orderBy('name')->get()
            : collect();
    }

    public function updatedDivisionId(): void
    {
        $this->district_id = null;
        $this->thana_id = null;
    }

    public function updatedDistrictId(): void
    {
        $this->thana_id = null;
    }

    public function startAdd(): void
    {
        $this->reset(['editingId', 'label', 'name', 'phone', 'division_id', 'district_id', 'thana_id', 'area', 'address_line', 'is_default']);
        $this->label = 'Home';
        $this->showForm = true;
    }

    public function startEdit(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();
        $address = $customer->addresses()->findOrFail($addressId);

        $this->editingId = $address->id;
        $this->label = $address->label;
        $this->name = $address->name;
        $this->phone = $address->phone;
        $this->division_id = $address->division_id;
        $this->district_id = $address->district_id;
        $this->thana_id = $address->thana_id;
        $this->area = $address->area ?? '';
        $this->address_line = $address->address_line;
        $this->is_default = $address->is_default;
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->reset(['editingId', 'label', 'name', 'phone', 'division_id', 'district_id', 'thana_id', 'area', 'address_line', 'is_default']);
    }

    public function save(): void
    {
        $validated = $this->validate();
        $customer = Auth::guard('customer')->user();

        if ($this->is_default) {
            $customer->addresses()->update(['is_default' => false]);
        }

        if ($this->editingId) {
            $address = $customer->addresses()->findOrFail($this->editingId);
            $address->update([...$validated, 'is_default' => $this->is_default]);
        } else {
            $customer->addresses()->create([...$validated, 'is_default' => $this->is_default]);
        }

        $this->cancelForm();
        $this->dispatch('address-saved');
    }

    public function delete(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();
        $customer->addresses()->findOrFail($addressId)->delete();
    }

    public function setDefault(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();
        $address = $customer->addresses()->findOrFail($addressId);

        $customer->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);
    }

    public function render()
    {
        return view('livewire.address-manager', [
            'addresses' => $this->addresses,
        ]);
    }
}
