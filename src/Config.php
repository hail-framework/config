<?php

namespace Hail\Config;

\defined('OPCACHE_INVALIDATE') || \define('OPCACHE_INVALIDATE', \function_exists('\opcache_invalidate'));

use Hail\Optimize\OptimizeTrait;
use Hail\Arrays\{ArrayTrait, Dot};

/**
 * Class Config
 *
 * @package Hail\Config
 */
class Config implements \ArrayAccess
{
    use ArrayTrait;
    use OptimizeTrait;

    public const KEY_ENV = 'env';
    public const KEY_CONFIG = 'config';
    public const KEY_LOADER = 'loader';

    /**
     * @var array
     */
    private $items = [];

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var string
     */
    private $folder;

    /**
     * @var LoaderInterface[]
     */
    private $loaders = [];

    /**
     * @var Env
     */
    public $env;

    public function __construct(array $options)
    {
        if (!isset($options[self::KEY_CONFIG]) || !\is_dir($options[self::KEY_CONFIG])) {
            throw new \InvalidArgumentException("Folder not exists '{$options[self::KEY_CONFIG]}'");
        }

        $this->folder = $options[self::KEY_CONFIG];

        if (isset($options[self::KEY_ENV])) {
            $this->env = new Env($options[self::KEY_ENV]);
        }

        if (isset($options[self::KEY_LOADER])) {
            if ($options[self::KEY_LOADER] instanceof LoaderInterface) {
                $this->loaders = [$options[self::KEY_LOADER]];
            } elseif (\is_array($options[self::KEY_LOADER])) {
                foreach ($options[self::KEY_LOADER] as $loader) {
                    if ($loader instanceof LoaderInterface) {
                        $this->loaders[] = $loader;
                    }
                }
            }
        }

        if ($this->loaders === []) {
            $this->loaders = [new Loader\Php()];
        }

        $this->items = new Dot([]);
    }

    public function addLoader(LoaderInterface $loader): self
    {
        $this->loaders[] = $loader;

        return $this;
    }

    /**
     * @param sif (!$this->support($file)) {
    throw new \InvalidArgumentException('The file is not supported');
    }tring $name
     *
     * @return string|null|Env
     */
    public function env(string $name = null)
    {
        if ($name === null) {
            return $this->env;
        }

        if ($this->env === null) {
            return Env::getenv($name);
        }

        return $this->env->get($name);
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function set(string $key, $value): void
    {
        $this->items[$key] = $value;
        $this->cache = [];
    }

    /**
     * Get the specified configuration value.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key)
    {
        if ($key === '' || $key === '.') {
            return null;
        }

        if (\array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $found = $this->items[$key];

        if ($found === null) {
            [$space] = $split = \explode('.', $key, 2);

            if (!isset($this->items[$space]) && ($found = $this->load($space)) !== null) {
                $this->items[$space] = $found;

                if (isset($split[1])) {
                    $found = $this->items[$key];
                }
            }
        }

        return $this->cache[$key] = $found;
    }

    public function delete(string $key): void
    {
        unset($this->items[$key]);
        $this->cache = [];
    }

    /**
     * Read config array from cache or file
     * Extensions order: php > yml > yaml
     *
     * @param string $space
     *
     * @return array|null
     */
    private function load(string $space): ?array
    {
        $space = $this->folder . DIRECTORY_SEPARATOR . $space;

        foreach ($this->loaders as $loader) {
            $file = $loader->find($space);
            if ($file !== null) {
                return $this->optimize()->load($file, [$loader, 'load']);
            }
        }

        return null;
    }

    public function modifyTime(string $key): ?int
    {
        $space = \explode('.', $key, 2)[0];

        $space = $this->folder . DIRECTORY_SEPARATOR . $space;

        foreach ($this->loaders as $loader) {
            $file = $loader->find($space);
            if ($file !== null) {
                return \filemtime($file);
            }
        }

        return null;
    }
}
