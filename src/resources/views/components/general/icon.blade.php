@props(['name'])

@php
    $iconPath = resource_path("svg/{$name}.svg");
    $svgContent = '';
    if (file_exists($iconPath)) {
        // We read the file and inject the attributes from the component call.
        // This allows you to pass classes, etc., directly to the <svg> tag.
        $svgContent = file_get_contents($iconPath);
        $svgContent = preg_replace('/<svg/i', '<svg ' . $attributes, $svgContent, 1);
    }
@endphp

{!! $svgContent !!}