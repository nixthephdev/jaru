<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Keep a copy of your menu</x-slot>
        <x-slot name="description">
            Downloads a zip of the menu database and all photos. Do it after big menu changes and keep it on your phone or Google Drive.
        </x-slot>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            To restore, extract the zip into the site folder. It overwrites <code>database/database.sqlite</code> and <code>public/uploads/</code>.
        </p>
    </x-filament::section>
</x-filament-panels::page>
