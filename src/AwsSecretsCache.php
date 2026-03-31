<?php

declare(strict_types=1);

namespace MoJ\AwsSecretsCache;

use Aws\SecretsManager\SecretsManagerClient;
use MoJ\AwsSecretsCache\Exception\InvalidSecretResponseException;
use Psr\SimpleCache\CacheInterface;

class AwsSecretsCache
{
    private const NS = 'aws';

    public function __construct(
        private readonly ?string $environment,
        private readonly CacheInterface $storage,
        private readonly SecretsManagerClient $client
    ) {
    }

    public function getValue(string $name): string
    {
        $qualifiedName = $this->qualify($name);

        $key = self::NS . ':' . $qualifiedName;



        
        if ($this->storage->has($key)) {
            $cached = $this->storage->get($key);
            return $cached;
        }

        $value = $this->getValueFromAWS($qualifiedName);
        $this->storage->set($key, $value);
        return $value;
    }

    protected function getValueFromAWS(string $qualifiedName): string
    {
        /**
         * @var array{
         *   SecretBinary?: string,
         *   SecretString?: string,
         * } $result
         */
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
        if ($this->storage->has($key)) {
            return (bool)$this->storage->delete($key);
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
