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

        $reflection = new ReflectionClass($clientClass);
        $client = $reflection->newInstance($config->uri, $config->options);

        if (!is_object($client)) {
            throw new \RuntimeException('Unable to create MongoDB client.');
        }

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
        $result = $this->invoke($this->collection($collection), 'insertOne', [$document]);
        $identifier = $this->invoke($result, 'getInsertedId');

        return (string) $identifier;
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
        $result = $this->invoke($this->collection($collection), 'updateMany', [$filter, $update]);
        $count = $this->invoke($result, 'getModifiedCount');

        return is_int($count) ? $count : (int) $count;
    }

    public function delete(string $collection, array $filter): int
    {
        $result = $this->invoke($this->collection($collection), 'deleteMany', [$filter]);
        $count = $this->invoke($result, 'getDeletedCount');

        return is_int($count) ? $count : (int) $count;
    }

    public function createIndex(string $collection, array $keys, bool $unique = false): string
    {
        $result = $this->invoke($this->collection($collection), 'createIndex', [$keys, ['unique' => $unique]]);

        return (string) $result;
    }

    public function collections(): array
    {
        $database = $this->invoke($this->client, 'selectDatabase', [$this->database]);
        $cursor = $this->invoke($database, 'listCollections');

        if (!is_iterable($cursor)) {
            throw new \UnexpectedValueException('MongoDB listCollections() did not return an iterable cursor.');
        }

        $collections = [];
        foreach ($cursor as $info) {
            $name = $this->invoke($info, 'getName');
            $collections[] = (string) $name;
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

        $collection = $this->invoke($this->client, 'selectCollection', [$this->database, $name]);

        if (!is_object($collection)) {
            throw new \UnexpectedValueException('MongoDB selectCollection() did not return an object.');
        }

        return $collection;
    }

    /** @param list<mixed> $arguments */
    private function invoke(object $target, string $method, array $arguments = []): mixed
    {
        if (!method_exists($target, $method)) {
            throw new \RuntimeException(sprintf('MongoDB adapter target does not implement %s().', $method));
        }

        return (new ReflectionMethod($target, $method))->invokeArgs($target, $arguments);
    }

    /** @return array<string, mixed> */
    private function normalizeDocument(mixed $document): array
    {
        if (is_array($document)) {
            return $document;
        }

        if (is_object($document) && method_exists($document, 'getArrayCopy')) {
            $value = $this->invoke($document, 'getArrayCopy');
            if (is_array($value)) {
                return $value;
            }
        }

        if ($document instanceof \JsonSerializable) {
            $value = $document->jsonSerialize();
            if (is_array($value)) {
                return $value;
            }
        }

        if (is_object($document)) {
            return get_object_vars($document);
        }

        throw new \UnexpectedValueException('Unable to normalize MongoDB document.');
    }
}
