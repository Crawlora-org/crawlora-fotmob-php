# Crawlora FotMob PHP client

This package calls the Crawlora hosted API at `https://api.crawlora.net/api/v1`. It does not call or scrape FotMob directly. Requests require a Crawlora API key and use your Crawlora account's service plan.

## Install

```sh
composer require crawlora/fotmob
```

Create an account at [crawlora.net](https://crawlora.net/signup), open the [Crawlora console](https://crawlora.net/app) to get an API key, then set `CRAWLORA_API_KEY` in your environment.

```php
<?php
require __DIR__ . '/vendor/autoload.php';

$client = new Crawlora\Fotmob\Client(apiKey: getenv('CRAWLORA_API_KEY'));
$result = $client->request("fotmob-search", ['term' => 'Premier League']);
print_r($result);
$client->close();
```

The client uses PHP cURL and JSON. Constructor options are `apiKey`, `baseUrl`, `timeout`, and an optional callable `transport` for tests. Call a generated method for direct access to each supported operation, or `request($operationId, $params, $responseType)` to dispatch by operation ID. Set `$responseType` to `text` for raw text output such as transcript formats. The package contains 31 operations and follows contract revision `sha256:d40e5ee2b400b1b40b5ca6998063cde26b6bb3e7f84da679b5047d26380f3fa1`.

See [Crawlora](https://crawlora.net/), the [API documentation](https://crawlora.net/docs), and [the package repository](https://github.com/Crawlora-org/crawlora-fotmob) for account setup and the complete operation reference.
