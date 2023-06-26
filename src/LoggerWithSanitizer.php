<?php

namespace PlacetopayOrg\GuzzleLogger;

use PlacetopayOrg\GuzzleLogger\Support\ArrHelper;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class LoggerWithSanitizer extends AbstractLogger
{
    public function __construct(private readonly LoggerInterface $logger, private readonly array $fieldsToSanitize = [])
    {
        //
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        if (! empty($this->fieldsToSanitize)) {
            $this->sanitizer($context, $this->fieldsToSanitize);
        }

        $this->logger->log($level, $message, $context);
    }

    private function sanitizer(array &$data, array $fields): void
    {
        foreach ($fields as $key => $format) {
            if (is_numeric($key)) {
                $key = $format;
                $format = '***';
            }

            if (is_callable($format)) {
                if ($value = ArrHelper::get($data, $key)) {
                    $format = $format($value);
                } else {
                    continue;
                }
            }

            ArrHelper::set($data, $key, $format);
        }
    }
}
