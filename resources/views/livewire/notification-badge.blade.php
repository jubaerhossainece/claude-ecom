<span class="relative">
    @if($count > 0)
        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
            {{ $count > 99 ? '99+' : $count }}
        </span>
    @endif
</span>
