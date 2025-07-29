<?php
if (!function_exists('help_generate_initials')) {
    function help_generate_initials(string $name): string
    {
        $name = trim($name);
        
        if (empty($name)) {
            return '';
        }
        
        // Split name and remove empty parts (without this, you would have an empty string as a part if there are multiple consecutive spaces)
        $nameParts = array_filter(explode(' ', $name), function($part) {
            return !empty(trim($part));
        });
        
        $nameCount = count($nameParts);
        
        if ($nameCount === 0) {
            return '';
        }
        
        if ($nameCount === 1) {
            // Single name: take first 2 characters
            return strtoupper(substr($nameParts[0], 0, 2));
        }
        
        // Multiple names: take first letter of first and last name
        $firstInitial = substr($nameParts[0], 0, 1);
        $lastInitial = substr($nameParts[$nameCount - 1], 0, 1);
        
        return strtoupper($firstInitial . $lastInitial);
    }
}

if (!function_exists('help_get_avatar_colors')) {
    function help_get_avatar_colors(string $initials): array
    {
        $colorPalette = [
            ['background' => 'bg-blue-100', 'text' => 'text-blue-800'],
            ['background' => 'bg-green-100', 'text' => 'text-green-800'],
            ['background' => 'bg-orange-100', 'text' => 'text-orange-800'],
            ['background' => 'bg-purple-100', 'text' => 'text-purple-800'],
            ['background' => 'bg-red-100', 'text' => 'text-red-800'],
            ['background' => 'bg-teal-100', 'text' => 'text-teal-800'],
            ['background' => 'bg-yellow-100', 'text' => 'text-yellow-800'],
            ['background' => 'bg-pink-100', 'text' => 'text-pink-800'],
            ['background' => 'bg-indigo-100', 'text' => 'text-indigo-800'],
            ['background' => 'bg-lime-100', 'text' => 'text-lime-800']
        ];
        
        // Generate consistent index based on initials
        $index = crc32($initials) % count($colorPalette);
        
        return $colorPalette[$index];
    }
}