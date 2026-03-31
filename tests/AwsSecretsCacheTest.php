<?php

declare(strict_types=1);

namespace MoJ\AwsSecretsCache\Tests;

use PHPUnit\Framework\TestCase;
use MoJ\AwsSecretsCache\AwsSecretsCache;
use Aws\SecretsManager\SecretsManagerClient;
use MoJ\AwsSecretsCache\Exception\InvalidSecretResponseException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\SimpleCache\CacheInterface;

class AwsSecretsCacheTest extends TestCase
{
    private CacheInterface&MockObject $cache;
    private SecretsManagerClient&MockObject $client;
    private AwsSecretsCache $sut;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->client = $this->createMock(SecretsManagerClient::class);

        $this->sut = new AwsSecretsCache('test', $this->cache, $this->client);
    }

    public function testGetValueReturnsCachedValue()
    {
        $this->cache->expects($this->once())
            ->method('has')
            ->with('aws:test/my-secret--fail')
            ->willReturn(true);

        $this->cache->expects($this->once())
            ->method('get')
            ->with('aws:test/my-secret')
            ->willReturn('cached-value');

        $value = $this->sut->getValue('my-secret');
        $this->assertEquals('cached-value', $value);
    }

    public function testGetValueFetchesValueFromAWS()
    {
        $this->cache->expects($this->once())
            ->method('has')
            ->with('aws:test/my-secret')
            ->willReturn(false);

        $this->client->expects($this->once())
            ->method('__call')
            ->with('getSecretValue', [['SecretId' => 'test/my-secret']])
            ->willReturn(['SecretString' => 'aws-value']);

        $this->cache->expects($this->once())
            ->method('set')
            ->with('aws:test/my-secret', 'aws-value');

        $value = $this->sut->getValue('my-secret');
        $this->assertEquals('aws-value', $value);
    }

    public function testGetValueFailsIfSecretDoesNotExist()
    {
        $this->cache->expects($this->once())
            ->method('has')
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
        $this->cache->expects($this->once())
            ->method('has')
            ->with('aws:test/my-secret')
            ->willReturn(true);

        $this->cache->expects($this->once())
            ->method('delete')
            ->with('aws:test/my-secret')
            ->willReturn(true);

        $result = $this->sut->clearCache('my-secret');
        $this->assertTrue($result);
    }
}
