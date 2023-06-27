# Guzzle Log Middleware

This is a middleware for [guzzle](https://github.com/guzzle/guzzle) that will help you automatically log every request
and response using a PSR-3 logger.

The middleware is functional with version 7 of Guzzle.

## Usage

### Simple usage

```php
use PlacetoPay\GuzzleLogger\Middleware\HttpLogMiddleware;
use GuzzleHttp\HandlerStack;

$logger = new Logger();  //A new PSR-3 Logger like Monolog
$stack = HandlerStack::create(); // will create a stack with middlewares of guzzle already pushed inside of it.
$stack->push(new HttpLogMiddleware($logger));
$client = new GuzzleHttp\Client([
    'handler' => $stack,
]);
```

From now on each request and response you execute using `$client` object will be logged.
By default, the middleware logs every activity with level `INFO`.

### With Sanitizer

```php
use PlacetoPay\GuzzleLogger\Middleware\HttpLogMiddleware;
use PlacetoPay\GuzzleLogger\LoggerWithSanitizer;
use PlacetoPay\GuzzleLogger\ValueSanitizer;
use GuzzleHttp\HandlerStack;


use GuzzleLogger\ValueSanitizer;

$fieldsToSanitize = [
            'request.body.instrument.card.number' => ValueSanitizer::CARD_NUMBER->value,
            'request.body.instrument.card.expiration' => ValueSanitizer::DEFAULT->value,
            'response.body.instrument.card.number' => fn ($value) => preg_replace('/(\d{6})(\d{3,9})(\d{4})/', '$1····$3', (string) $value)
];

$stack = HandlerStack::create();
$logger = new LoggerWithSanitizer(new Logger(), $fieldsToSanitize)
$stack->push(new HttpLogMiddleware($logger));
$client = new GuzzleHttp\Client([
    'handler' => $stack,
]);
```
With the LoggerWithSanitizer class you can obfuscate or sanitize sensitive data when logging.

fieldsToSanitize is a key-value array, with which you can determine the level of data to sanitize and the value with which to obfuscate the value. 
If you do not send a value and the value is found it will be sanitized with a default value `ValueSanitizer::DEFAULT`.


### Using options on each request

You can set on each request options about your log.

 ```php
 $client->get('/', [
     'log' => [
         'statistics' => true,
     ]
 ]);
 ```

- ``statistics`` option is true and this is also true the middleware will log statistics about the HTTP call.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
