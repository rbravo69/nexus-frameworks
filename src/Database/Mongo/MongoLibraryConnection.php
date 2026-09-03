<?php

declare(strict_types=1);

namespace Nexus\Database\Mongo;

use ReflectionClass;
use ReflectionMethod;

final class MongoLibraryConnection implements MongoConnectionInterface
{
    private function __construct(
        private readonly object $client,
        private readonly string $database,
    ) {
    }

    public static function connect(MongoConfig $config): self
    {
        $clientClass = 'MongoDB\\Client';

        if (!class_exists($clientClass)) {
            throw new \RuntimeException(
                'The official MongoDB adapter requires ext-mongodb and mongodb/mongodb.',
            );
        }

        /** @var class-string $clientClass */
        $reflection = new ReflectionClass($clientClass);
        $client = $reflection->newInstance($config->uri, $config->options);

        return new self($client, $config->database);
    }

    public static function fromClient(object $client, string $database): self
    {
        if ($database === '') {
            throw new \InvalidArgumentException('MongoDB database cannot be empty.');
        }

        return new self($client, $database);
    }

    public function insert(string $collection, array $document): string
    {
        $result = $this->objectResult($this->invoke($this->collection($collection), 'insertOne', [$document]), 'insertOne');
        return $this->stringResult($this->invoke($result, 'getInsertedId'), 'getInsertedId');
    }

    public function find(string $collection, array $filter = []): array
    {
        $cursor = $this->invoke($this->collection($collection), 'find', [$filter]);

        if (!is_iterable($cursor)) {
            throw new \UnexpectedValueException('MongoDB find() did not return an iterable cursor.');
        }

        $documents = [];
        foreach ($cursor as $document) {
            $documents[] = $this->normalizeDocument($document);
        }

        return $documents;
    }

    public function update(string $collection, array $filter, array $update): int
    {
        $result = $this->objectResult($this->invoke($this->collection($collection), 'updateMany', [$filter, $update]), 'updateMany');
        return $this->intResult($this->invoke($result, 'getModifiedCount'), 'getModifiedCount');
    }

    public function delete(string $collection, array $filter): int
    {
        $result = $this->objectResult($this->invoke($this->collection($collection), 'deleteMany', [$filter]), 'deleteMany');
        return $this->intResult($this->invoke($result, 'getDeletedCount'), 'getDeletedCount');
    }

    public function createIndex(string $collection, array $keys, bool $unique = false): string
    {
        return $this->stringResult(
            $this->invoke($this->collection($collection), 'createIndex', [$keys, ['unique' => $unique]]),
            'createIndex',
        );
    }

    public function collections(): array
    {
        $database = $this->objectResult($this->invoke($this->client, 'selectDatabase', [$this->database]), 'selectDatabase');
        $cursor = $this->invoke($database, 'listCollections');

        if (!is_iterable($cursor)) {
            throw new \UnexpectedValueException('MongoDB listCollections() did not return an iterable cursor.');
        }

        $collections = [];
        foreach ($cursor as $info) {
            $infoObject = $this->objectResult($info, 'listCollections');
            $collections[] = $this->stringResult($this->invoke($infoObject, 'getName'), 'getName');
        }

        sort($collections);
        return $collections;
    }

    public function indexes(string $collection): array
    {
        $cursor = $this->invoke($this->collection($collection), 'listIndexes');

        if (!is_iterable($cursor)) {
            throw new \UnexpectedValueException('MongoDB listIndexes() did not return an iterable cursor.');
        }

        $indexes = [];
        foreach ($cursor as $index) {
            $indexes[] = $this->normalizeDocument($index);
        }

        return $indexes;
    }

    private function collection(string $name): object
    {
        if ($name === '') {
            throw new \InvalidArgumentException('MongoDB collection cannot be empty.');
        }

        return $this->objectResult(
            $this->invoke($this->client, 'selectCollection', [$this->database, $name]),
            'selectCollection',
        );
    }

    /** @param list<mixed> $arguments */
    private function invoke(object $target, string $method, array $arguments = []): mixed
    {
        if (!method_exists($target, $method)) {
            throw new \RuntimeException(sprintf('MongoDB adapter target does not implement %s().', $method));
        }

        return (new ReflectionMethod($target, $method))->invokeArgs($target, $arguments);
    }

    private function objectResult(mixed $value, string $method): object
    {
        if (!is_object($value)) {
            throw new \UnexpectedValueException(sprintf('MongoDB %s() did not return an object.', $method));
        }
        return $value;
    }

    private function stringResult(mixed $value, string $method): string
    {
        if (is_string($value) || is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if ($value instanceof \Stringable) {
            return (string) $value;
        }
        throw new \UnexpectedValueException(sprintf('MongoDB %s() did not return a stringable value.', $method));
    }

    private function intResult(mixed $value, string $method): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        throw new \UnexpectedValueException(sprintf('MongoDB %s() did not return an integer value.', $method));
    }

    /** @return array<string, mixed> */
    private function normalizeDocument(mixed $document): array
    {
        if (is_array($document)) {
            return $this->stringKeyed($document);
        }

        if (is_object($document) && method_exists($document, 'getArrayCopy')) {
            $value = $this->invoke($document, 'getArrayCopy');
            if (is_array($value)) {
                return $this->stringKeyed($value);
            }
        }

        if ($document instanceof \JsonSerializable) {
            $value = $document->jsonSerialize();
            if (is_array($value)) {
                return $this->stringKeyed($value);
            }
        }

        if (is_object($document)) {
            return $this->stringKeyed(get_object_vars($document));
        }

        throw new \UnexpectedValueException('Unable to normalize MongoDB document.');
    }

    /**
     * @param array<array-key, mixed> $value
     * @return array<string, mixed>
     */
    private function stringKeyed(array $value): array
    {
        $normalized = [];
        foreach ($value as $key => $item) {
            $normalized[(string) $key] = $item;
        }
        return $normalized;
    }
}
