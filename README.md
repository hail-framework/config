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

$env = new Env(__DIR__);
$env->get('ENVIRONMENT');

use Hail\Config\Config;

$cachePath = __DIR__ . DIRECTORY_SEPARATOR . 'cache';
$options = [
    'path' => __DIR__, // same as 'path' => ['env' => __DIR__, 'config' => __DIR__ . DIRECTORY_SEPARATOR . 'config'],
    'loaders' => [  // if empty, the default loader is Hail\Config\Loader\Php 
        Config::loader('yaml', $cachePath),
        Config::loader('json', $cachePath)
    ]
];
$config = new Config(...$options);

$config->addLoader(
    new Hail\Config\Loader\Php()
);

$config->get('filename.key.sub');

$env->get('ENVIRONMENT') === $config->env('ENVIRONMENT'); //true
$config->env->get('ENVIRONMENT') === $config->env('ENVIRONMENT'); //true
$config->env === $config->env(); //true
```
