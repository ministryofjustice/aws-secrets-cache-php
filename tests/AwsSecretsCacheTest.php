<?php

declare(strict_types=1);

namespace MoJ\AwsSecretsCache\Tests;

use PHPUnit\Framework\TestCase;
use MoJ\AwsSecretsCache\AwsSecretsCache;
use Aws\SecretsManager\SecretsManagerClient;
use Laminas\Cache\Storage\StorageInterface;
use MoJ\AwsSecretsCache\Exception\InvalidSecretResponseException;
use PHPUnit\Framework\MockObject\MockObject;

class AwsSecretsCacheTest extends TestCase
{
    private StorageInterface&MockObject $storage;
    private SecretsManagerClient&MockObject $client;
    private AwsSecretsCache $sut;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(StorageInterface::class);
        $this->client = $this->createMock(SecretsManagerClient::class);

        $this->sut = new AwsSecretsCache('test', $this->storage, $this->client);
    }

    public function testGetValueReturnsCachedValue()
    {
        $this->storage->expects($this->once())
            ->method('hasItem')
            ->with('aws:test/my-secret')
            ->willReturn(true);

        $this->storage->expects($this->once())
            ->method('getItem')
            ->with('aws:test/my-secret')
            ->willReturn('cached-value');

        $value = $this->sut->getValue('my-secret');
        $this->assertEquals('cached-value', $value);
    }

    public function testGetValueFetchesValueFromAWS()
    {
        $this->storage->expects($this->once())
            ->method('hasItem')
            ->with('aws:test/my-secret')
            ->willReturn(false);

        $this->client->expects($this->once())
            ->method('__call')
            ->with('getSecretValue', [['SecretId' => 'test/my-secret']])
            ->willReturn(['SecretString' => 'aws-value']);

        $this->storage->expects($this->once())
            ->method('setItem')
            ->with('aws:test/my-secret', 'aws-value');

        $value = $this->sut->getValue('my-secret');
        $this->assertEquals('aws-value', $value);
    }

    public function testGetValueFailsIfSecretDoesNotExist()
    {
        $this->storage->expects($this->once())
            ->method('hasItem')
            ->with('aws:test/my-secret')
            ->willReturn(false);

        $this->client->expects($this->once())
            ->method('__call')
            ->with('getSecretValue', [['SecretId' => 'test/my-secret']])
            ->willReturn([]);

        $this->expectException(InvalidSecretResponseException::class);
        $this->sut->getValue('my-secret');
    }

    public function testClearCacheRemovesCachedValue()
    {
        $this->storage->expects($this->once())
            ->method('hasItem')
            ->with('aws:test/my-secret')
            ->willReturn(true);

        $this->storage->expects($this->once())
            ->method('removeItem')
            ->with('aws:test/my-secret')
            ->willReturn(true);

        $result = $this->sut->clearCache('my-secret');
        $this->assertTrue($result);
    }
}
