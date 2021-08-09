<?php

namespace Hail\Config;

use Hail\Optimize\OptimizeTrait;
use Hail\Arrays\{ArrayAccessTrait, Dot};

/**
 * Class Config
 *
 * @package Hail\Config
 */
class Config implements \ArrayAccess
{
    use ArrayAccessTrait;
    use OptimizeTrait;

    private Dot $items;

    private array $cache = [];

    private string $folder;

    /**
     * @var LoaderInterface[]
     */
    private array $loaders = [];

    public Env $env;

    public function __construct(string|array $path, array $loaders = null)
    {
        if (\is_string($path)) {
            $envPath = $path;

        } else {
            [
                'env' => $envPath,
                'config' => $configPath,
            ] = $path;
        }

        $this->env = new Env($envPath);

        $configPath ??= $envPath . DIRECTORY_SEPARATOR . 'config';
        if (!\is_dir($configPath)) {
            throw new \InvalidArgumentException("'$configPath' is not exists");
        }
        $this->folder = $configPath;

        if ($loaders !== null) {
            foreach ($loaders as $loader) {
                if ($loader instanceof LoaderInterface) {
                    $this->loaders[] = $loader;
                }
            }
        }

        if ($this->loaders === []) {
            $this->loaders[] = new Loader\Php();
        }

        $this->items = new Dot([]);
    }

    public static function loader(string $type, string $cachePath = null): LoaderInterface
    {
        return match ($type) {
            'yaml' => new Loader\Yaml($cachePath),
            'toml' => new Loader\Toml($cachePath),
            'json' => new Loader\Json($cachePath),
            'ini' => new Loader\Ini($cachePath),
            'php' => new Loader\Php(),
            default => throw new \RuntimeException("'$type' loader not supported"),
        };
    }

    public function addLoader(LoaderInterface $loader): self
    {
        $this->loaders[] = $loader;

        return $this;
    }

    public function env(string $name = null): ?string
    {
        return $this->env->get($name);
    }

    public function set(string $key, mixed $value): void
    {
        $this->items->set($key, $value);
        $this->cache = [];
    }

    public function get(string $key): mixed
    {
        if ($key === '' || $key === '.') {
            return null;
        }

        if (\array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $found = $this->items->get($key);

        if ($found === null) {
            [$space] = $split = \explode('.', $key, 2);

            if (!$this->items->has($space) && ($found = $this->load($space)) !== null) {
                $this->items->set($space, $found);

                if (isset($split[1])) {
                    $found = $this->items->get($key);
                }
            }
        }

        return $this->cache[$key] = $found;
    }

    public function delete(string $key): void
    {
        $this->items->delete($key);
        $this->cache = [];
    }

    /**
     * Read config array from cache or file
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
