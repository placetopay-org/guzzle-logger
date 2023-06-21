<?php

namespace PlacetopayOrg\GuzzleLogger\DTO;

class HttpLogConfig
{

    public function __construct(
        public string $message = 'Guzzle HTTP'
    )
    {
    }
}