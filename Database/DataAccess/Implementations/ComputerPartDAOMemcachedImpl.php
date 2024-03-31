<?php

namespace Database\DataAccess\Implementations;

use Database\DataAccess\Interfaces\ComputerPartDAO;
use Database\DatabaseManager;
use DateTime;
use Models\ComputerPart;
use Memcached;
use Models\DataTimeStamp;

class ComputerPartDAOMemcachedImpl implements ComputerPartDAO
{
    private Memcached $memcached;

    public function __construct()
    {
        $this->memcached = DatabaseManager::getMemcachedConnection();
    }

    private function computerPartToResult(ComputerPart $part): array{
        return [
            'name' => $part->getName(),
            'type' => $part->getType(),
            'brand' => $part->getBrand(),
            'id' => $part->getId(),
            'model_number' => $part->getModelNumber(),
            'release_date' => $part->getReleaseDate(),
            'description' => $part->getDescription(),
            'performance_score' => $part->getPerformanceScore(),
            'market_price' => $part->getMarketPrice(),
            'rsm' => $part->getRsm(),
            'power_consumption_w' => $part->getPowerConsumptionW(),
            'length_m' => $part->getLengthM(),
            'width_m' => $part->getWidthM(),
            'height_m' => $part->getHeightM(),
            'lifespan' => $part->getLifespan(),
            'created_at' => $part->getTimeStamp()->getCreatedAt(),
            'updated_at' => $part->getTimeStamp()->getUpdatedAt(),
        ];
    }

    private function resultToComputerPart(array $data): ComputerPart {
        $timestamp = isset($data['created_at']) && isset($data['updated_at']) ? new DataTimeStamp(
            $data['created_at'],
            $data['updated_at']
        ) : null;

        return new ComputerPart(
            name: $data['name'],
            type: $data['type'],
            brand: $data['brand'],
            id: $data['id'] ?? null,
            modelNumber: $data['model_number'] ?? null,
            releaseDate: $data['release_date'] ?? null,
            description: $data['description'] ?? null,
            performanceScore: $data['performance_score'] ?? null,
            marketPrice: $data['market_price'] ?? null,
            rsm: $data['rsm'] ?? null,
            powerConsumptionW: $data['power_consumption_w'] ?? null,
            lengthM: $data['length_m'] ?? null,
            widthM: $data['width_m'] ?? null,
            heightM: $data['height_m'] ?? null,
            lifespan: $data['lifespan'] ?? null,
            timeStamp: $timestamp,
        );
    }

    public function create(ComputerPart $partData): bool
    {
        if($partData->getId() !== null) throw new \Exception('Cannot create a computer part with an existing ID. id: ' . $partData->getId());

        // 保存されたアイテム数に基づくid
        $stats = $this->memcached->getStats();
        $firstServerKey = key($stats);
        if($stats === false) throw new \Exception("Failed to retrieve cache stats.");
        $itemCount = $stats[$firstServerKey]['curr_items'];

        $partData->setId($itemCount);
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $partData->setTimeStamp(new DataTimeStamp($now, $now));
        return $this->memcached->set("ComputerPart_{$partData->getId()}", json_encode($this->computerPartToResult($partData)));
    }

    public function getById(int $id): ?ComputerPart
    {
        $result = $this->memcached->get("ComputerPart_$id");
        return $result ? $this->resultToComputerPart(json_decode($result, true)) : null;
    }

    public function update(ComputerPart $partData): bool
    {
        if($partData->getId() === null) throw new \Exception('Computer part specified has no ID.');

        $partData->getTimeStamp()->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));
        return $this->memcached->set("ComputerPart_{$partData->getId()}", json_encode($this->computerPartToResult($partData)));
    }

    public function delete(int $id): bool
    {
        return $this->memcached->delete("ComputerPart_$id");
    }

    public function createOrUpdate(ComputerPart $partData): bool
    {
        if($partData->getId() !== null) return $this->update($partData);
        else return $this->create($partData);
    }

    public function getRandom(): ?ComputerPart
    {
        // クエリを使わないので、単純なO(n)アプローチです。
        $keys = $this->memcached->getAllKeys();
        $computerPartKeys = array_filter($keys, fn($key) => str_starts_with($key, "ComputerPart_"));

        if (empty($computerPartKeys)) return null;

        $randomKey = $computerPartKeys[array_rand($computerPartKeys)];
        $result = $this->memcached->get($randomKey);

        return $result ? $this->resultToComputerPart(json_decode($result, true)) : null;
    }

    // getAllByType関数とgetAll関数は、クエリがないため、O(n)のアプローチを使用します。
    // しかし、すべてのキーをソートしており、sort()はクイックソートを使用するため、平均O(n log n)となります。
    public function getAll(int $offset, int $limit): array
    {
        $memcached = $this->memcached;
        $keys = $memcached->getAllKeys();
        $computerPartKeys = array_filter($keys, fn($key) => str_starts_with($key, "ComputerPart_"));

        $keys = sort($keys, SORT_STRING);

        $selectedKeys = array_slice($computerPartKeys, $offset, $limit);
        $parts = array_map(function($key) use ($memcached) {
            $result = $memcached->get($key);
            return $result ? $this->resultToComputerPart(json_decode($result)) : null;
        }, $selectedKeys);

        return array_filter($parts, fn($part) => $part !== false);
    }

    public function getAllByType(string $type, int $offset, int $limit): array
    {
        $allParts = $this->getAll(0, PHP_INT_MAX);
        $filteredParts = array_filter($allParts, fn(ComputerPart $part) => $part->getType() === $type);

        return array_slice($filteredParts, $offset, $limit);
    }
}