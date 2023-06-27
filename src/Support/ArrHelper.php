<?php

namespace PlacetoPay\GuzzleLogger\Support;

use ArrayAccess;
use Closure;

class ArrHelper
{
    /**
     * Set an array item to a given value using "dot" notation.
     * Taken from 'Illuminate\Support\Arr::set'.
     */
    public static function set(array &$array, string $key, string $value)
    {
        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Get an item from an array using "dot" notation.
     * Taken from 'Illuminate\Support\Arr::get'.
     */
    public static function get($array, $key, $default = null)
    {
        if (! static::accessible($array)) {
            return self::value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        if (! str_contains((string) $key, '.')) {
            return $array[$key] ?? self::value($default);
        }

        foreach (explode('.', (string) $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return self::value($default);
            }
        }

        return $array;
    }

    /**
     *  Determine if the given key exists in the provided array..
     *  Taken from 'Illuminate\Support\Arr::exists'.
     */
    public static function exists($array, $key): bool
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        if (is_float($key)) {
            $key = (string) $key;
        }

        return array_key_exists($key, $array);
    }

    /**
     * Determine whether the given value is array accessible.
     *  Taken from 'Illuminate\Support\Arr::accessible'.
     */
    public static function accessible($value): bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    private static function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}
