@props([
    'channel' => null,
    'other' => '',
    'required' => false,
    'inputClass' => 'w-full text-sm rounded-xl border-gray-300 py-2 px-3 focus:ring-brand-800 focus:border-brand-800',
    'labelClass' => 'block text-xs font-semibold text-gray-700 mb-1',
])
@php
    $selected = $channel instanceof \App\Enums\TransferChannel ? $channel->value : (string) $channel;
    $isOther = $selected === \App\Enums\TransferChannel::Lainnya->value;
@endphp
<div {{ $attributes->merge(['class' => 'contents']) }} data-transfer-channel-box>
    <div>
        <label class="{{ $labelClass }}">Transfer Lewat <span class="text-rose-500">*</span></label>
        <select name="transfer_channel" data-transfer-channel {{ $required ? 'required' : '' }} data-bank-required="1"
                onchange="toggleTransferChannelOther(this)" class="{{ $inputClass }}">
            <option value="" {{ $selected === '' ? 'selected' : '' }}>-- Pilih Bank / Dompet Digital --</option>
            <optgroup label="Bank">
                @foreach(\App\Enums\TransferChannel::banks() as $option)
                    <option value="{{ $option->value }}" {{ $selected === $option->value ? 'selected' : '' }}>{{ $option->label() }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Dompet Digital">
                @foreach(\App\Enums\TransferChannel::digitalWallets() as $option)
                    <option value="{{ $option->value }}" {{ $selected === $option->value ? 'selected' : '' }}>{{ $option->label() }}</option>
                @endforeach
            </optgroup>
            <option value="{{ \App\Enums\TransferChannel::Lainnya->value }}" {{ $isOther ? 'selected' : '' }}>{{ \App\Enums\TransferChannel::Lainnya->label() }}</option>
        </select>
    </div>
    <div data-transfer-channel-other-box class="{{ $isOther ? '' : 'hidden' }}">
        <label class="{{ $labelClass }}">Nama Bank / Dompet Digital Lainnya <span class="text-rose-500">*</span></label>
        <input type="text" name="transfer_channel_other" value="{{ $other }}" maxlength="50" data-transfer-channel-other
               {{ $isOther ? 'required' : '' }} placeholder="Contoh: Bank Jatim, Flip, Astra Pay"
               class="{{ $inputClass }}">
    </div>
</div>
@once
<script>
    // Kolom isian bebas hanya muncul (dan wajib) saat memilih Lainnya.
    function toggleTransferChannelOther(select) {
        const box = select.closest('[data-transfer-channel-box]');
        if (!box) return;
        const otherBox = box.querySelector('[data-transfer-channel-other-box]');
        const other = box.querySelector('[data-transfer-channel-other]');
        const isOther = select.value === @json(\App\Enums\TransferChannel::Lainnya->value);
        otherBox.classList.toggle('hidden', !isOther);
        other.required = isOther && select.required;
        if (!isOther) other.value = '';
    }

    // Isi pilihan saluran dari nama tersimpan; nama di luar daftar jadi Lainnya.
    function setTransferChannel(box, name) {
        const select = box.querySelector('[data-transfer-channel]');
        const other = box.querySelector('[data-transfer-channel-other]');
        if (!select) return;
        const known = Array.from(select.options).some(function (option) { return option.value === name; });
        if (!name) {
            select.value = '';
        } else if (known) {
            select.value = name;
        } else {
            select.value = @json(\App\Enums\TransferChannel::Lainnya->value);
        }
        toggleTransferChannelOther(select);
        if (other && !known && name) other.value = name;
    }
</script>
@endonce
