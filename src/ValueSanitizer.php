<?php

namespace PlacetopayOrg\GuzzleLogger;

enum ValueSanitizer: string
{
    case DEFAULT = 'DEFAULT';
    case CARD_NUMBER = 'CARD_NUMBER';

    public static function cardNumber(string $value): string
    {
        return preg_replace('/(\d{6})(\d{3,9})(\d{4})/', '$1*****$3', $value);
    }

    public static function default()
    {
        return '****';
    }

    public static function sanitize($format, $value)
    {
        if (is_callable($format)){
            return $format($value);
        }

        return match ($format) {
            self::DEFAULT => self::default(),
            self::CARD_NUMBER => self::cardNumber($value),
            default => $format,
        };
    }
}