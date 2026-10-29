<?php

namespace App\Support;

class CategoryColor
{
    private const PALETTE = [
        'forest' => '#3b4a3c',
        'sage' => '#b7c3a3',
        'rose' => '#d9b9bd',
        'blush' => '#e8d3d4',
        'taupe' => '#cfcdc3',
        'gold' => '#dcc875',
    ];

    public static function resolve(?string $color, string $fallback): string
    {
        $color = self::PALETTE[$color] ?? $color;

        return is_string($color) && preg_match('/\A#(?:[0-9a-f]{3}|[0-9a-f]{6})\z/i', $color)
            ? $color
            : $fallback;
    }
}
