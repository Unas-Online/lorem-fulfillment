<?php

namespace App\Services;

use App\Utils\Filesystem;
use DateTime;
use DateTimeZone;
use UnasOnline\UnasConnect\Api\ClientCacheInterface;

class Storage implements ClientCacheInterface
{
    private string $shopId;
    private string $baseDir;
    
    public function __construct(string $appRoot, string $shop_id)
    {
        $this->shopId = $shop_id;
        $this->baseDir = $appRoot . '/data/' . $this->shopId;
        Filesystem::ensureDirectoryExists($this->baseDir);
        Filesystem::ensureDirectoryExists($this->baseDir . '/orders');
    }

    /**
     * Update local state of the specified order
     *
     * @param array $order
     * @return void
     */
    public function updateOrder(array $order): void
    {
        file_put_contents($this->baseDir . '/orders/' . $order['Id'] . '.json', json_encode($order));
    }

    /**
     * Get all locally stored orders as an array for $this->shopId
     *
     * @return array
     */
    public function getOrders(): array
    {
        $orders = $this->getOrderPaths();
        return array_map(fn($name) => (array)json_decode(file_get_contents($name), true), $orders);
    }

    /**
     * Delete all locally stored orders for $this->shopId
     *
     * @return void
     */
    public function deleteOrders(): void
    {
        $orders = $this->getOrderPaths();
        foreach ($orders as $order) {
            unlink($order);
        }
    }

    /**
     * Checks for the existence of a settings file for $this->shopId
     *
     * @return bool true if settings file exists, false otherwise
     */
    public function hasSettings(): bool
    {
        return file_exists($this->baseDir . '/settings.json');
    }

    /**
     * Get all settings as an array for $this->shopId
     *
     * @return array
     */
    public function getSettings(): array
    {
        $path = $this->baseDir . '/settings.json';
        if (!file_exists($path)) {
            return [];
        }

        return (array)json_decode(file_get_contents($path));
    }

    /**
     * Overwrite all settings for $this->shopId
     *
     * @return void
     */
    public function setSettings(array $settings): void
    {
        $path = $this->baseDir . '/settings.json';
        file_put_contents($path, json_encode($settings));
    }

    /**
     * Get a list of all locally stored order json files for $this->shopId
     *
     * @return array list of order file paths
     */
    private function getOrderPaths(): array
    {
        $orders = scandir($this->baseDir . '/orders');
        $orders = array_values(array_filter($orders, fn($name) => $name[0] != '.'));
        return array_map(fn($name) => $this->baseDir . '/orders/' . $name, $orders);
    }

    /**
     * Get locally stored automatism id for $this->shopId
     *
     * @return ?string automatism id, null if not set
     */
    public function getAutomatismId(): ?string
    {
        $path = $this->baseDir . '/automatism_id.txt';
        if (file_exists($path)) {
            return file_get_contents($path);
        }
        
        return null;
    }

    /**
     * Update locally stored automatism id for $this->shopId
     *
     * @param string $id
     * @return void
     */
    public function setAutomatismId(string $id): void
    {
        $path = $this->baseDir . '/automatism_id.txt';
        file_put_contents($path, $id);
    }

    /**
     * Get locally stored api key for $this->shopId
     *
     * @return ?string api key, null if not set
     */
    public function getApiKey(): ?string
    {
        $path = $this->baseDir . '/api_key.txt';
        if (file_exists($path)) {
            return file_get_contents($path);
        }
        
        return null;
    }

    /**
     * Update locally stored api key for $this->shopId
     *
     * @param string $apiKey
     * @return void
     */
    public function setApiKey(string $apiKey): void
    {
        file_put_contents($this->baseDir . '/api_key.txt', $apiKey);
    }

    /**
     * Get locally stored api token for $this->shopId
     *
     * @return ?string api token, null if not set
     */
    public function getApiToken(): ?string
    {
        $tokenData = $this->getApiTokenData();
        if (is_null($tokenData)) {
            return null;
        }
        
        $expire = new DateTime(str_replace('.', '-', $tokenData['Expire']), new DateTimeZone('Europe/Budapest'));
        $now = new DateTime('now', new DateTimeZone('Europe/Budapest'));

        if ($expire <= $now) {
            $this->deleteApiToken();
            return false;
        }

        return $tokenData['Token'];
    }

    /**
     * Get latest successful login response for $this->shopId
     *
     * @return ?array login response
     */
    public function getApiTokenData(): ?array
    {
        $path = $this->baseDir . '/api_token.json';
        if (!file_exists($path)) {
            return null;
        }
        
        $tokenDataJson = file_get_contents($path);
        if ($tokenDataJson === false) {
            return null;
        }

        return (array)json_decode($tokenDataJson, true);
    }

    /**
     * Checks whether UNAS subscription is premium
     *
     * @return bool true if the user has a premium subscription, false otherwise
     */
    public function isPremium(): bool
    {
        $tokenData = $this->getApiTokenData();
        if (is_null($tokenData)) {
            return false;
        }

        return str_starts_with($tokenData['Subscription'], 'premium');
    }

    /**
     * Checks whether UNAS subscription is vip
     *
     * @return bool true if the user has a vip subscription, false otherwise
     */
    public function isVip(): bool
    {
        $tokenData = $this->getApiTokenData();
        if (is_null($tokenData) || !array_key_exists('Subscription', $tokenData)) {
            return false;
        }

        return str_starts_with($tokenData['Subscription'], 'vip');
    }

    /**
     * Update locally stored api token for $this->shopId
     *
     * @param string $token
     * @return void
     */
    public function setApiToken(array $token): void
    {
        file_put_contents($this->baseDir . '/api_token.json', json_encode($token));
    }

    /**
     * Delete locally stored api key for $this->shopId
     *
     * @return void
     */
    public function deleteApiToken(): void
    {
        $path = $this->baseDir . '/api_token.json';
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function cacheUnasApiLogin(array $data): void
    {
        $this->deleteApiToken();
        $this->setApiToken($data);
    }

    public function restoreUnasApiLogin(): ?array
    {
        return $this->getApiTokenData();
    }
}
