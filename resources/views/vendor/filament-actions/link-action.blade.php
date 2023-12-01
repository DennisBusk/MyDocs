<x-filament-actions::action
    :action="$action"
    :badge="$getBadge()"
    :badge-color="$getBadgeColor()"
    dynamic-component="filament::link"
    :icon-position="$getIconPosition()"
    :size="$getSize()"
    class="fi-ac-link-action"
{{--    wire:navigate.hover--}}
>
    {{ $getLabel() }}
</x-filament-actions::action>
