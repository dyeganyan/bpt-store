### Installing via Composer

The recommended way to install BPT Store is through
[Composer](http://getcomposer.org).

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
```

Next, run the Composer command to install the latest stable version of Guzzle:

```bash
composer require businessprocess/bpt-store
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```

Init Client
```php
$client = new \Kialex\BptStore\Client([
    // Necessary
    //
    'login' => 'bpt_store_login',
    'password' => 'bpt_store_password',
    'url' => 'https://fs-api-dev.webwellness.net/api/v1/',
    'sandbox' => true, // `false` is Production mode. Default is `false`
    //
    // Optional
    //
    // 'maxAttempts' => 5 // Attempts to reconnect if something went wrong, Default is `3`
    // 'versionNumber' => 1 // API version number, Default is `1`
    // See `DEV_URl` and `PROD_URL` constants of class
]);

$bptFileCloud = new \Kialex\BptStore\File($client);
```

Push file to BPT storage
```php
$fileData = $bptFileCloud->add(
    'path_to_file', // Full path to file or Absolute URL
    445566, // Group Id
    true // If U want to create a private file -> set `false`. Default is `true`.
);
```

Example content of `$fileData`
```json
{
    "uuid": "6a29d6bd9267491ab84c6d65280fba1658b6ebbd1689275b408feab2f187e367",
    "name": "Example_File.png",
    "size": 117185,
    "mimeType": "image/png",
    "hash": "58b6ebbd1689275b408feab2f187e367"
}
```

Get public file URL
```php
$publicUrl = $bptFileCloud->getPublicUrl('58b6ebbd1689275b408feab2f187e367') // Put `hash` from `$fileData`;
```

Get private file URL
```php
$publicUrl = $bptFileCloud->getPrivateUrl('6a29d6bd9267491ab84c6d65280fba1658b6ebbd1689275b408feab2f187e367') // Put `uuid` from `$fileData`;
```
