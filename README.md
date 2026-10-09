# Crawlora FotMob PHP client

This package calls the Crawlora hosted API at `https://api.crawlora.net/api/v1`. It does not call or scrape FotMob directly. Requests require a Crawlora API key and use your Crawlora account's service plan.

## Install

```sh
composer require crawlora/fotmob
```

Create an account at [crawlora.net](https://crawlora.net/signup?utm_source=packagist&utm_medium=referral&utm_campaign=platform-clients&utm_content=fotmob-php-signup), open the [Crawlora console](https://crawlora.net/app?utm_source=packagist&utm_medium=referral&utm_campaign=platform-clients&utm_content=fotmob-php-console) to get an API key, then set `CRAWLORA_API_KEY` in your environment.

```php
<?php
require __DIR__ . '/vendor/autoload.php';

$client = new Crawlora\Fotmob\Client(apiKey: getenv('CRAWLORA_API_KEY'));
$result = $client->request("fotmob-search", ['term' => 'Premier League']);
print_r($result);
$client->close();
```

The client uses PHP cURL and JSON. Constructor options are `apiKey`, `baseUrl`, and `timeout`. Call the operation-specific method for direct access to each supported operation, or `request($operationId, $params, $responseType)` to dispatch by operation ID. Set `$responseType` to `text` for raw text output such as transcript formats. The package includes 31 API operations.

See [Crawlora](https://crawlora.net/?utm_source=packagist&utm_medium=referral&utm_campaign=platform-clients&utm_content=fotmob-php-homepage), the [API documentation](https://crawlora.net/docs?utm_source=packagist&utm_medium=referral&utm_campaign=platform-clients&utm_content=fotmob-php-api-docs), and [the PHP package source](https://github.com/Crawlora-org/crawlora-fotmob-php). The complete operation and parameter reference is in the [platform repository](https://github.com/Crawlora-org/crawlora-fotmob/blob/main/docs/usage.md).
