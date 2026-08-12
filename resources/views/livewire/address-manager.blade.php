<div>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-800">Saved Addresses</h2>
        @unless($showForm)
            <button type="button" wire:click="startAdd" class="text-sm font-semibold text-primary border border-primary rounded-lg px-3 py-1.5 hover:bg-primary hover:text-white transition-colors">
                + Add Address
            </button>
        @endunless
    </div>

    @if($showForm)
        <form wire:submit="save" class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-4 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
                    <select wire:model="label" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                        <option value="Home">Home</option>
                        <option value="Office">Office</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input wire:model="name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                    <input wire:model="phone" type="tel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                    @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Area / Locality</label>
                    <input wire:model="area" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Division *</label>
                    <select wire:model.live="division_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                        <option value="">Select Division</option>
                        @foreach($this->divisions as $div)
                            <option value="{{ $div->id }}">{{ $div->name }}</option>
                        @endforeach
                    </select>
                    @error('division_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">District *</label>
                    <select wire:model.live="district_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 disabled:bg-gray-100" {{ !$division_id ? 'disabled' : '' }}>
                        <option value="">Select District</option>
                        @foreach($this->districts as $dist)
                            <option value="{{ $dist->id }}">{{ $dist->name }}</option>
                        @endforeach
                    </select>
                    @error('district_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Thana/Upazila *</label>
                    <select wire:model="thana_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 disabled:bg-gray-100" {{ !$district_id ? 'disabled' : '' }}>
                        <option value="">Select Thana</option>
                        @foreach($this->thanas as $thana)
                            <option value="{{ $thana->id }}">{{ $thana->name }}</option>
                        @endforeach
                    </select>
                    @error('thana_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Address *</label>
                <input wire:model="address_line" type="text" placeholder="House, Road, Block..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                @error('address_line') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input wire:model="is_default" type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary/30">
                Set as default address
            </label>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary px-4 py-2 text-sm">
                    {{ $editingId ? 'Update Address' : 'Save Address' }}
                </button>
                <button type="button" wire:click="cancelForm" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
            </div>
        </form>
    @endif

    @forelse($addresses as $address)
        <div class="flex items-start justify-between gap-4 border border-gray-200 rounded-xl p-4 mb-3">
            <div class="text-sm">
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-semibold text-gray-800">{{ $address->label }}</span>
                    @if($address->is_default)
                        <span class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full font-medium">Default</span>
                    @endif
                </div>
                <p class="text-gray-700">{{ $address->name }} — {{ $address->phone }}</p>
                <p class="text-gray-500">{{ $address->full_address }}</p>
            </div>
            <div class="flex flex-col gap-2 text-xs flex-shrink-0 items-end">
                <button type="button" wire:click="startEdit({{ $address->id }})" class="text-primary hover:underline">Edit</button>
                @unless($address->is_default)
                    <button type="button" wire:click="setDefault({{ $address->id }})" class="text-gray-500 hover:text-primary">Set Default</button>
                @endunless
                <button type="button" wire:click="delete({{ $address->id }})" wire:confirm="Delete this address?" class="text-red-500 hover:text-red-700">Delete</button>
            </div>
        </div>
    @empty
        @unless($showForm)
            <p class="text-sm text-gray-500">No saved addresses yet.</p>
        @endunless
    @endforelse
</div>
