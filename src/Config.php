<?php

namespace Hail\Config;

\defined('OPCACHE_INVALIDATE') || \define('OPCACHE_INVALIDATE', \function_exists('\opcache_invalidate'));

use Hail\Optimize\OptimizeTrait;
use Hail\Serializer\Yaml;
use Hail\Arrays\{ArrayTrait, Arrays, Dot};

/**
 * Class Config
 *
 * @package Hail\Config
 */
class Config implements \ArrayAccess
{
    use ArrayTrait;
    use OptimizeTrait;

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
    private $cacheFolder;
    private $yaml;

    public function __construct(string $folder, string $cacheFolder = null)
    {
        if (!\is_dir($err = $folder) || ($cacheFolder && !\is_dir($err = $cacheFolder))) {
            throw new \InvalidArgumentException("Folder not exists '$err'");
        }

        $this->folder = $folder;
        $this->cacheFolder = $cacheFolder;

        $this->items = new Dot([]);

        static::optimizePrefix($folder);
        static::optimizeReader(['.yml', '.yaml'], [$this, 'loadYaml']);
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
     * @param  string $key
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
        $file = $this->getFile($space);

        if ($file === null) {
            return null;
        }

        return static::optimizeLoad($file);
    }

    /**
     * Parse a YAML file or load it from the cache
     *
     * @param string $file
     *
     * @return array|mixed
     */
    private function loadYaml(string $file)
    {
        if ($this->cacheFolder === null) {
            $content = $this->decodeYaml($file);

            return $this->parseArray($content);
        }

        $ext = \strrchr($file, '.');

        $dir = $this->cacheFolder;
        $filename = \basename($file);
        $cache = $dir . DIRECTORY_SEPARATOR . \substr($filename, 0, -\strlen($ext)) . '.php';

        if (@\filemtime($cache) < \filemtime($file)) {
            $content = $this->decodeYaml($file);

            if (!\is_dir($dir) && !@\mkdir($dir, 0755) && !\is_dir($dir)) {
                throw new \RuntimeException('Temp directory permission denied');
            }

            \file_put_contents($cache, '<?php return ' . $this->parseArrayCode($content) . ';');

            if (OPCACHE_INVALIDATE) {
                \opcache_invalidate($cache, true);
            }
        }

        return include $cache;
    }

    private function decodeYaml(string $file): array
    {
        if ($this->yaml === null) {
            $this->yaml = Yaml::getInstance();
        }

        return $this->yaml->decode(
            \file_get_contents($file)
        );
    }

    private function parseArray(array $array): array
    {
        foreach ($array as &$v) {
            if (\is_array($v)) {
                $v = $this->parseArray($v);
            } else {
                $v = $this->parseValue($v);
            }
        }

        return $array;
    }

    private function parseArrayCode(array $array, int $level = 0): string
    {
        $pad = '';
        if ($level > 0) {
            $pad = \str_repeat("\t", $level);
        }

        $isAssoc = Arrays::isAssoc($array);

        $ret = '[' . "\n";
        foreach ($array as $k => $v) {
            $ret .= $pad . "\t";
            if ($isAssoc) {
                $ret .= \var_export($k, true) . ' => ';
            }

            if (\is_array($v)) {
                $ret .= $this->parseArrayCode($v, $level + 1);
            } else {
                $ret .= $this->parseValueCode($v);
            }

            $ret .= ',' . "\n";
        }

        return $ret . $pad . ']';
    }

    private function parseValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        if (\preg_match('/%([a-zA-Z0-9_:\\\]+)(?::(.*))?%/', $value, $matches)) {
            $function = $matches[1];

            if (!\function_exists($function)) {
                return $value;
            }

            if (!isset($matches[2])) {
                return $function();
            }

            $args = \explode(',', $matches[2]);
            foreach ($args as &$a) {
                $a = $this->parseConstant(\trim($a));
            }

            return $function(...$args);
        }

        return $this->parseConstant($value);
    }

    /**
     * Parse
     *
     * @param mixed $value
     *
     * @return string
     */
    private function parseValueCode($value): string
    {
        if ($value instanceof \DateTime) {
            return 'new \\DateTime(' . \var_export($value->format('c'), true) . ')';
        }

        if (!\is_string($value)) {
            return \var_export($value, true);
        }

        if (\preg_match('/%([a-zA-Z0-9_:\\\]+)(?::(.*))?%/', $value, $matches)) {
            $function = $matches[1];

            if (!\function_exists($function)) {
                return \var_export($value, true);
            }

            if (!isset($matches[2])) {
                return $function . '()';
            }

            $args = \explode(',', $matches[2]);
            foreach ($args as &$a) {
                $a = $this->parseConstantCode(\trim($a));
            }

            return $function . '(' . \implode(', ', $args) . ')';
        }

        return $this->parseConstantCode($value);
    }

    private function parseConstant(string $value): string
    {
        return \preg_replace_callback('/\${([a-zA-Z0-9_:\\\]+)}/', static function ($matches) {
            return \defined($matches[1]) ? \constant($matches[1]) : $matches[0];
        }, $value);
    }

    private function parseConstantCode(string $value): string
    {
        $value = \var_export($value, true);

        \preg_match_all('/\${([a-zA-Z0-9_:\\\]+)}/', $value, $matches);

        if (!empty($matches[0])) {
            $replace = [];
            foreach ($matches[0] as $k => $v) {
                $replace[$v] = '\' . ' . \str_replace('\\\\', '\\', $matches[1][$k]) . ' . \'';
            }

            if ($replace !== []) {
                $value = \strtr($value, $replace);

                $start = 0;
                if (\strpos($value, "'' . ") === 0) {
                    $start = 5;
                }

                $end = null;
                if (\strrpos($value, " . ''", 5) > 0) {
                    $end = -5;
                }

                if ($end !== null) {
                    $value = \substr($value, $start, $end);
                } elseif ($start !== 0) {
                    $value = \substr($value, $start);
                }
            }
        }

        return $value;
    }

    private function getFile(string $space): ?string
    {
        foreach (['.php', '.yml', '.yaml'] as $ext) {
            $real = $this->folder . DIRECTORY_SEPARATOR . $space . $ext;
            if (\file_exists($real)) {
                return $real;
            }
        }

        return null;
    }

    public function modifyTime(string $key): ?int
    {
        $file = $this->getFile(
            \explode('.', $key, 2)[0]
        );

        if ($file === null) {
            return null;
        }

        return \filemtime($file);
    }
}
