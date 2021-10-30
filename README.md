# binance-request
Simplified client for making request for binance

## Examples

#### Example 1

```php
require_once __DIR__ . '/vendor/autoload.php';

$ma = new \BinanceRequest\Account(65, 'test', 'your api key', 'your api secret');
$req = \BinanceRequest\Request::getBinanceRequest();

$res = $req->getAccountInfo($ma)->wait();

print_r($res['positions'][0]);

foreach ($res['positions'] as $position) {
    if ($position['positionAmt'] > 0) {
        print_r($position);
    }
}
```
