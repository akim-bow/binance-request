<?php

namespace BinanceRequest;

class Account
{
    public int $id;
    public string $name;
    public string $apiKey;
    public string $apiSecret;
    protected array $symbolData = [];

    public function __construct(int $id, string $name, string $apiKey, string $apiSecret) {
        $this->id = $id;
        $this->name = $name;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    public function __toString() {
        return md5($this->name . $this->apiKey . $this->apiSecret);
    }
}