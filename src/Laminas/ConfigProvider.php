<?php

declare(strict_types=1);

namespace MoJ\AwsSecretsCache\Laminas;

use MoJ\AwsSecretsCache\AwsSecretsCache;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                'factories' => [
                    AwsSecretsCache::class => AwsSecretsCacheFactory::class,
                ],
            ],
            // sensible defaults; app can override
            'aws_secrets_cache' => [
                'environment' => getenv('ENVIRONMENT') ?: '',
            ],
        ];
    }
}