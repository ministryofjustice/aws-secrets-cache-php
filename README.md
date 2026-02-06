# aws-secrets-cache-php

Reusable helper class to fetch secrets at runtime from AWS Secrets Manager with a cache to reduce duplicate requests.

Requires a pre-configured cache implementing `StorageInterface` from [laminas-cache](https://docs.laminas.dev/laminas-cache/).

## Installation

```sh
composer require ministryofjustice/aws-secrets-cache-php
```

## Usage

```php
use Aws\SecretsManager\SecretsManagerClient;
use Laminas\Cache\Storage\StorageInterface;
use MoJ\AwsSecretsCache\AwsSecretsCache;

$storage = /** instanceof StorageInterface */;
$smClient = new SecretsManagerClient([...]);

$secretsCache = new AwsSecretsCache(null, $storage, $smClient);

$mySecret = $secretsCache->getValue('my-secret');
```

The first parameter optionally defines a namespace  that will apply to all secrets retrieved by that instance of the class. The following example would return the secret named `namespace/my-secret`:

```php
$secretsCache = new AwsSecretsCache('namespace', ...);
$myNamespacedSecret = $secretsCache->getValue('my-secret');
```

You can use `clearCache` to remove an item from the underlying cache:

```php
$mySecret = $secretsCache->getValue('my-secret');

$secretsCache->clearCache('my-secret');

$mySecret = $secretsCache->getValue('my-secret'); // Will fetch from AWS again
```

TTL must be implemented on the storage class, it is not applied by `AwsSecretsCache`.
