<?php

namespace App\Helpers;

use Carbon\Carbon;

class TodoListHelper
{
    /**
     * Convert comma-separated string to array.
     */
    public static function convertStringToArray($string): array
    {
        if (empty($string)) {
            return [];
        }
        
        return array_map('trim', explode(',', $string));
    }

    public static function convertArrayToString(array $array): string
    {
        return implode(',', array_map('trim', $array));
    }

    /**
     * Format date to 'dd MMM, yyyy' format.
     */
    public static function formatDate($date): string
    {
        if (empty($date)) {
            return '';
        }
        
        return Carbon::parse($date)->format('d M, Y');
    }

    /**
     * Format enum values: capitalize and replace underscores with spaces.
     */
    public static function formatEnumValue($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        
        return ucwords(str_replace('_', ' ', $value));
    }
}
