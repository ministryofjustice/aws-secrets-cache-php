<?php

declare(strict_types=1);

namespace MoJ\AwsSecretsCache;

use MoJ\AwsSecretsCache\Laminas\AwsSecretsCacheFactory;
use MoJ\AwsSecretsCache\AwsSecretsCache;
use Laminas\ModuleManager\Feature\ConfigProviderInterface;

class Module implements ConfigProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getConfig()
    {
        return [
            'service_manager' => [
                'factories' => [
                    AwsSecretsCache::class => AwsSecretsCacheFactory::class,
                ],
            ],
        ];
    }
}
