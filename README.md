# Config

# Example
```php
use Hail\Config\Env

$env = new Env(__DIR__ . DIRECTORY_SEPARATOR . Env::FILE);
$env->get('ENVIRONMENT');

use Hail\Config\Config;
$config = new Config([
    Config::KEY_ENV => __DIR__ . DIRECTORY_SEPARATOR . Env::FILE, // env files
    Config::KEY_CONFIG => __DIR__ . DIRECTORY_SEPARATOR . 'config', // config file dir
    Config::KEY_CACHE => __DIR__ . DIRECTORY_SEPARATOR . 'cache',   // php cache dir for yaml
]);

$config->get('filename.key.sub');
$env->get('ENVIRONMENT') === $config->env('ENVIRONMENT'); //true
$config->env->get('ENVIRONMENT') === $config->env('ENVIRONMENT'); //true
```
