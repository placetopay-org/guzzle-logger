<?php

namespace PlacetopayOrg\GuzzleLogger\Helpers;

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

    public static function get(array $array, string $key)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (! str_contains($key, '.')) {
            return $array[$key] ?? null;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return null;
            }
        }

        return $array;
    }
}
