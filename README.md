# Config

# Example
```php
use Hail\Config\Env

$env = new Env([
    __DIR__ . DIRECTORY_SEPARATOR . Env::FILE
]);
$env->get('ENVIRONMENT');

use Hail\Config\Config;
$config = new Config(
    __DIR__ . DIRECTORY_SEPARATOR . 'config', // config file dir
    __DIR__ . DIRECTORY_SEPARATOR . 'cache'   // php cache dir for yaml
);

$config->get('filename.key.sub');


```
