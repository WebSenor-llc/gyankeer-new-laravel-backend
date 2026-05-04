@extends('layouts.app')

@section('title', ($isEdit ? 'Edit ' : 'Add ') . $singular)

@section('content')
<div class="p-4 max-w-5xl mx-auto">
    <div class="text-xs text-slate-500 mb-2">
        HR / Master Config /
        <a href="{{ route($routeBase . '.index') }}" class="hover:underline">{{ $title }}</a> /
        <span class="text-slate-900 font-semibold">{{ $isEdit ? 'Edit' : 'Add' }}</span>
    </div>

    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold">{{ $isEdit ? 'Edit' : 'Add' }} {{ $singular }}</h1>
        <a href="{{ route($routeBase . '.index') }}" class="tb-btn">← Back to list</a>
    </div>

    @if($errors->any())
        <div class="mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-800">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $isEdit ? route($routeBase . '.update', $record->{$pkName}) : route($routeBase . '.store') }}"
          class="card p-5 space-y-4">
        @csrf
        @if($isEdit) @method('PATCH') @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($fields as $f)
                @php
                    $name     = $f['name'];
                    $label    = $f['label'] ?? str_replace('_', ' ', ucfirst($name));
                    $type     = $f['type'] ?? 'text';
                    $value    = old($name, $record->{$name} ?? '');
                    $required = $f['required'] ?? false;
                    $col      = $f['col'] ?? 1;
                    $colClass = $col === 2 ? 'md:col-span-2' : '';
                @endphp

                <div class="{{ $colClass }}">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="{{ $name }}">
                        {{ $label }}
                        @if($required) <span class="text-red-500">*</span> @endif
                    </label>

                    @if($type === 'textarea')
                        <textarea id="{{ $name }}" name="{{ $name }}" rows="3"
                                  class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm focus:outline-none focus:border-[var(--brand)]"
                                  @if($required) required @endif>{{ $value }}</textarea>

                    @elseif($type === 'boolean')
                        <label class="flex items-center gap-2 mt-2">
                            <input type="checkbox" name="{{ $name }}" value="1"
                                   class="rounded border-slate-300 text-[var(--brand)] focus:ring-[var(--brand)]"
                                   @if(old($name, $record->{$name} ?? false)) checked @endif>
                            <span class="text-sm text-slate-700">Enabled</span>
                        </label>

                    @elseif($type === 'select')
                        <select id="{{ $name }}" name="{{ $name }}"
                                class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm focus:outline-none focus:border-[var(--brand)]"
                                @if($required) required @endif>
                            <option value="">— Select —</option>
                            @foreach($f['options'] ?? [] as $optVal => $optLabel)
                                <option value="{{ $optVal }}" @selected((string)$value === (string)$optVal)>{{ $optLabel }}</option>
                            @endforeach
                        </select>

                    @elseif($type === 'date')
                        <input type="date" id="{{ $name }}" name="{{ $name }}"
                               value="{{ $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value }}"
                               class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm focus:outline-none focus:border-[var(--brand)]"
                               @if($required) required @endif>

                    @elseif($type === 'time')
                        <input type="time" id="{{ $name }}" name="{{ $name }}"
                               value="{{ $value }}"
                               class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm focus:outline-none focus:border-[var(--brand)]"
                               @if($required) required @endif>

                    @elseif($type === 'number')
                        <input type="number" id="{{ $name }}" name="{{ $name }}"
                               value="{{ $value }}" step="{{ $f['step'] ?? 'any' }}"
                               class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm focus:outline-none focus:border-[var(--brand)]"
                               @if($required) required @endif>

                    @elseif($type === 'email')
                        <input type="email" id="{{ $name }}" name="{{ $name }}"
                               value="{{ $value }}"
                               class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm focus:outline-none focus:border-[var(--brand)]"
                               @if($required) required @endif>

                    @else
                        <input type="text" id="{{ $name }}" name="{{ $name }}"
                               value="{{ $value }}"
                               class="block w-full border border-[var(--line)] rounded-lg p-2 text-sm focus:outline-none focus:border-[var(--brand)]"
                               @if($required) required @endif>
                    @endif

                    @if(!empty($f['help']))
                        <p class="text-[11px] text-slate-400 mt-1">{{ $f['help'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-[var(--line)]">
            <a href="{{ route($routeBase . '.index') }}" class="tb-btn">Cancel</a>
            <button type="submit" class="tb-btn primary">{{ $isEdit ? 'Save Changes' : 'Create ' . $singular }}</button>
        </div>
    </form>
</div>
@endsection
