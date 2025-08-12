<?php

declare(strict_types=1);

namespace MoJ\AwsSecretsCache\Laminas;

use Aws\SecretsManager\SecretsManagerClient;
use Laminas\Cache\Storage\Adapter\Apcu;
use Laminas\Cache\Storage\Plugin\ExceptionHandler;
use Laminas\ServiceManager\Factory\FactoryInterface;
use MoJ\AwsSecretsCache\AwsSecretsCache;
use Psr\Container\ContainerInterface;

final class AwsSecretsCacheFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param null|array<mixed> $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AwsSecretsCache
    {
        $config = $container->get('config');

        // Environment: prefer package config, then legacy sirius key, then ENV var
        $environment = $config['aws_secrets_cache']['environment']
            ?? $config['sirius']['environment']
            ?? (getenv('ENVIRONMENT') ?: '');

        // Laminas APCu storage with swallowed exceptions (match your diff)
        $storage = new Apcu();
        $plugin = new ExceptionHandler();
        $plugin->getOptions()->setThrowExceptions(false);
        $storage->addPlugin($plugin);

        // AWS client: prefer existing app-level aws config if present
        $awsConfig = $config['aws'] ?? ['region' => getenv('AWS_REGION') ?: 'eu-west-1', 'version' => 'latest'];
        $client = new SecretsManagerClient($awsConfig);

        // Optional: attach telemetry middleware when available
        if (class_exists('Telemetry\\Middleware\\Aws')) {
            /** @var callable $listener */
            $listener = ['Telemetry\\Middleware\\Aws', 'listen'];
            // guard in case signature differs
            try {
                $client->getHandlerList()->appendSign($listener($client), 'telemetry');
            } catch (\Throwable $e) {
                // ignore if not compatible
            }
        }

        return new AwsSecretsCache($environment, $storage, $client);
    }
}