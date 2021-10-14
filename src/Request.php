<?php

namespace BinanceRequest;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;

class Request
{
    private static ?Request $binanceRequest = null;
    private Client $httpClient;
    private const BASE_URI = 'https://fapi.binance.com';

    private function __construct() {
        $this->httpClient = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://fapi.binance.com',
            'timeout' => 5.0,
        ]);
    }

    public static function getBinanceRequest(): Request {
        if (static::$binanceRequest === null) {
            static::$binanceRequest = new Request();
        }
        return static::$binanceRequest;
    }

    public function getListenKey(Account $account): PromiseInterface {
        return $this->requestAsync("post", "/fapi/v1/listenKey", [
            'account' => $account
        ]);
    }

    public function requestAsync(
        string $method, string $url, array $options = []
    ): PromiseInterface {
        $request = $this->getRequest($method, $url, $options);

        return $this->httpClient->sendAsync($request);
    }

    private function getRequest(string $method, string $url, array $options): \GuzzleHttp\Psr7\Request {
        $params = $options['params'] ?? [];
        /** @var Account|null $account */
        $account = $options['account'] ?? null;

        $params = array_map(function ($param) {
            if (gettype($param) === "boolean") {
                return $param === true ? "true" : "false";
            }
            return $param;
        }, $params);

        $params['timestamp'] = $this->getTimestamp();

        if ($account) {
            $params['signature'] = $this->getSignature($account, http_build_query($params));
        }

        $uri = Uri::withQueryValues(new Uri(self::BASE_URI . $url), $params);

        $request = new \GuzzleHttp\Psr7\Request($method, $uri);

        if ($account) {
            $request = $request->withHeader('X-MBX-APIKEY', $account->apiKey);
        }

        return $request;
    }

    private function getTimestamp(): int {
        return floor(microtime(true) * 1000);
    }

    private function getSignature(Account $account, string $queryString): string {
        return hash_hmac("sha256", $queryString, $account->apiSecret);
    }

    public function extendListenKey(Account $account): PromiseInterface {
        return $this->requestAsync("put", "/fapi/v1/listenKey", [
            'account' => $account
        ]);
    }

    public function closeListenKey(Account $account): PromiseInterface {
        return $this->requestAsync("delete", "/fapi/v1/listenKey", [
            'account' => $account
        ]);
    }

    public function getExchangeData(): PromiseInterface {
        return $this->requestAsync("get", "/fapi/v1/exchangeInfo"
        )->then(function ($response) {
            return $response["symbols"];
        });
    }

    public function getAccountBalance(
        Account $account,
        string $asset = Constants::DEFAULT_ASSET
    ): PromiseInterface {
        return $this->requestAsync("get", "/fapi/v2/balance", [
            'account' => $account,
        ])->then(function ($response) use ($asset) {
            return Utils::arrayFind(
                $response,
                fn($accountInfo) => $accountInfo['asset'] === $asset
            );
        });
    }

    public function createNewOrder(
        Account $account, array $orderParams
    ): PromiseInterface {
        return $this->requestAsync("post", "/fapi/v1/order", [
            'account' => $account,
            'params' => $orderParams
        ]);
    }

    public function cancelOrder(
        Account $account,
        string  $symbol,
        string  $orderId
    ): PromiseInterface {
        return $this->requestAsync("delete", "/fapi/v1/order", [
            'account' => $account,
            'params' => ['symbol' => $symbol, 'orderId' => $orderId]
        ]);
    }

    public function cancelAllOrders(
        Account $account,
        string  $symbol
    ): PromiseInterface {
        return $this->requestAsync("delete", "/fapi/v1/allOpenOrders", [
            'account' => $account,
            'params' => ['symbol' => $symbol],
        ]);
    }

    public function cancelMultipleOrders(Account $account, string $symbol, array $orderIdList): PromiseInterface {
        return $this->requestAsync("delete", "/fapi/v1/batchOrders", [
            'account' => $account,
            'params' => ['symbol' => $symbol, 'orderIdList' => $orderIdList],
        ]);
    }

    public function getSymbolPrice(string $assetPair): PromiseInterface {
        return $this->requestAsync("get", "/fapi/v1/ticker/price", [
            'params' => ['symbol' => $assetPair]
        ])->then(function ($response) {
            return floatval($response['price']);
        });
    }

    public function getLeverage(Account $account, string $symbol): PromiseInterface {
        return $this->requestAsync("get", "/fapi/v2/account", [
            'account' => $account
        ])->then(function ($response) use ($symbol) {
            $position = Utils::arrayFind(
                $response['positions'],
                fn($value) => $value['symbol'] === $symbol
            );
            if (!$position) {
                throw new Exception("Cannot get correct leverage");
            }
            return $position['leverage'];
        });
    }

    public function setLeverage(
        Account $account, string $symbol, int $leverage
    ): PromiseInterface {
        return $this->requestAsync("post", "/fapi/v1/leverage", [
            'params' => ['symbol' => $symbol, 'leverage' => $leverage],
            'account' => $account
        ]);
    }

    public function getPositionMode(
        Account $account
    ): PromiseInterface {
        return $this->requestAsync("get", "/fapi/v1/positionSide/dual", [
            'account' => $account
        ])->then(function ($response) {
            return $response['dualSidePosition'] ? "true" : "false";
        });
    }

    public function setPositionMode(
        Account $account, string $positionMode
    ): PromiseInterface {
        return $this->requestAsync("post", "/fapi/v1/positionSide/dual", [
            'params' => ['dualSidePosition' => $positionMode],
            'account' => $account
        ]);
    }

    public function setMarginType(
        Account $account, string $symbol, string $marginType
    ): PromiseInterface {
        return $this->requestAsync("post", "/fapi/v1/marginType", [
            'params' => ['symbol' => $symbol, 'marginType' => $marginType],
            'account' => $account
        ]);
    }

    public function modifyIsolatedMargin(
        Account $account, string $symbol, float $amount
    ): PromiseInterface {
        return new FulfilledPromise('');
    }

    public function getAccountInfo(Account $account): PromiseInterface {
        return $this->requestAsync("get", "/fapi/v2/account", [
            'account' => $account
        ]);
    }

    public function getIncomeHistory(Account $account, array $incomeParams = []): PromiseInterface {
        return $this->requestAsync("get", "/fapi/v1/income", [
            'params' => $incomeParams,
            'account' => $account
        ]);
    }

    public function getAllOrders(Account $account, array $orderParams): PromiseInterface {
        return $this->requestAsync("get", "/fapi/v1/allOrders", [
            'params' => $orderParams,
            'account' => $account
        ]);
    }

    public function getAllOpenOrders(Account $account, string $symbol = ''): PromiseInterface {
        $params = $symbol ? ['symbol' => $symbol] : [];
        return $this->requestAsync("get", "/fapi/v1/openOrders", [
            'params' => $params,
            'account' => $account
        ]);
    }
}