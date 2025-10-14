<?php
/**
 * Polyfill per funzioni PHP 8.0+ per compatibilità con PHP 7.x
 * Includere questo file per garantire compatibilità backward
 * 
 * NOTA IMPORTANTE:
 * - Arrow functions (fn) richiedono PHP 7.4+
 * - Per compatibilità PHP 7.0-7.3 usare function() { return ...; }
 * - Match expressions richiedono PHP 8.0+
 * - Promoted properties richiedono PHP 8.0+
 */

/**
 * Polyfill per str_contains() per compatibilità PHP < 8.0
 */
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return strpos($haystack, $needle) !== false;
    }
}

/**
 * Polyfill per str_starts_with() per compatibilità PHP < 8.0
 */
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return strpos($haystack, $needle) === 0;
    }
}

/**
 * Polyfill per str_ends_with() per compatibilità PHP < 8.0
 */
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return substr($haystack, -strlen($needle)) === $needle;
    }
}

/**
 * Polyfill per fdiv() per compatibilità PHP < 8.0
 */
if (!function_exists('fdiv')) {
    function fdiv(float $num1, float $num2): float {
        return $num2 == 0 ? INF : $num1 / $num2;
    }
}

/**
 * Polyfill per get_debug_type() per compatibilità PHP < 8.0
 */
if (!function_exists('get_debug_type')) {
    function get_debug_type($value): string {
        switch (true) {
            case is_null($value):
                return 'null';
            case is_bool($value):
                return 'bool';
            case is_int($value):
                return 'int';
            case is_float($value):
                return 'float';
            case is_string($value):
                return 'string';
            case is_array($value):
                return 'array';
            case is_object($value):
                return get_class($value);
            case is_resource($value):
                return 'resource (' . get_resource_type($value) . ')';
            default:
                return 'unknown type';
        }
    }
}