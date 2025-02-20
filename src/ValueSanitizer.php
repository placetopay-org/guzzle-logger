<?php

namespace PlacetoPay\GuzzleLogger;

enum ValueSanitizer: string
{
    case DEFAULT = 'DEFAULT';

    case CARD_NUMBER = 'CARD_NUMBER';

    public static function cardNumber(string $value): string
    {
        $length = strlen($value);

        if ($length <= 5) {
            return substr($value, 0, 1) . str_repeat('*', $length - 1);
        }

        if ($length <= 11) {
            return substr($value, 0, 1) . str_repeat('*', $length - 2) . substr($value, -1);
        }

        return substr($value, 0, 6) . str_repeat('*', $length - 10) . substr($value, -4);
    }

    public static function default(): string
    {
        return '****';
    }

    public static function sanitize($format, $value): mixed
    {
        if (is_callable($format)) {
            return $format($value);
        }

        return match ($format) {
            self::DEFAULT => self::default(),
            self::CARD_NUMBER => self::cardNumber($value),
            default => $format,
        };
    }
}
