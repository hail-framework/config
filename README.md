# Config

# Loaders
## .yml / .yaml
Hail\Config\Loader\Yaml
```yaml
key:
  sub: true  
```
## .json
Hail\Config\Loader\Json
```json
{
  "key": {
    "sub": true
  }
}
```
## .php
Hail\Config\Loader\Php
```php
<?php
return [
    'key' => [
        'sub' => true
    ],
];

```
# Example
```php
use Hail\Config\Env

$env = new Env(__DIR__ . DIRECTORY_SEPARATOR . Env::FILE);
$env->get('ENVIRONMENT');

use Hail\Config\Config;

$config = new Config([
    Config::ENV => __DIR__ . DIRECTORY_SEPARATOR . Env::FILE, // env files
    Config::CONFIG => __DIR__ . DIRECTORY_SEPARATOR . 'config', // config file dir
    Config::LOADERS => [
        new Hail\Config\Loader\Yaml(__DIR__ . DIRECTORY_SEPARATOR . 'cache'),
        new Hail\Config\Loader\Json(__DIR__ . DIRECTORY_SEPARATOR . 'cache'),
    ], // default loader is Hail\Config\Loader\Php 
]);

$config->addLoader(
    new Hail\Config\Loader\Php()
);

$config->get('filename.key.sub');

$env->get('ENVIRONMENT') === $config->env('ENVIRONMENT'); //true
$config->env->get('ENVIRONMENT') === $config->env('ENVIRONMENT'); //true
$config->env === $config->env(); //true
```
