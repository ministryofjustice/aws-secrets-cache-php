<?php

declare(strict_types=1);

namespace MoJ\AwsSecretsCache;

use Aws\SecretsManager\SecretsManagerClient;
use Laminas\Cache\Storage\StorageInterface;
use MoJ\AwsSecretsCache\Exception\InvalidSecretResponseException;

class AwsSecretsCache
{
    private const NS = 'aws';

    public function __construct(
        private readonly ?string $environment,
        private readonly StorageInterface $storage,
        private readonly SecretsManagerClient $client
    ) {}

    public function getValue(string $name): string
    {
        $qualifiedName = $this->qualify($name);

        $key = self::NS . ':' . $qualifiedName;

        if ($this->storage->hasItem($key)) {
            /** @var string $cached */
            $cached = $this->storage->getItem($key);
            return $cached;
        }

        $value = $this->getValueFromAWS($qualifiedName);
        $this->storage->setItem($key, $value);
        return $value;
    }

    protected function getValueFromAWS(string $qualifiedName): string
    {
        $result = $this->client->getSecretValue(['SecretId' => $qualifiedName]);

        $secret = false;
        if (isset($result['SecretString'])) {
            $secret = $result['SecretString'];
        } elseif (isset($result['SecretBinary'])) {
            $secret = base64_decode((string)$result['SecretBinary']);
        }

        if ($secret === false) {
            throw new InvalidSecretResponseException('No value returned for requested key ' . $qualifiedName);
        }

        return (string)$secret;
    }

    public function clearCache(string $name): bool
    {
        $qualifiedName = $this->qualify($name);

        $key = self::NS . ':' . $qualifiedName;
        if ($this->storage->hasItem($key)) {
            return (bool)$this->storage->removeItem($key);
        }
        return false;
    }

    private function qualify(string $name): string
    {
        $name = ltrim($name, '/');

        if ($this->environment === null || $this->environment === '') {
            return $name;
        }

        $envPrefix = rtrim($this->environment, '/') . '/';
        if (str_starts_with($name, $envPrefix)) {
            return $name; // already qualified
        }

        return $envPrefix . $name;
    }
}