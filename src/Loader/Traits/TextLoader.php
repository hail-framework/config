<?php

namespace Hail\Config\Loader\Traits;

\defined('OPCACHE_INVALIDATE') || \define('OPCACHE_INVALIDATE', \function_exists('\opcache_invalidate'));

use Hail\Arrays\Arrays;

trait TextLoader
{
    use Loader;

    private ?string $cacheDir = null;

    public function __construct(string $cacheDir = null)
    {
        if ($cacheDir && \is_dir($cacheDir)) {
            $this->cacheDir = $cacheDir;
        }
    }

    abstract protected function decode(string $file): array;

    public function load(string $file): array
    {
        if (!$this->support($file)) {
            throw new \InvalidArgumentException('The file is not supported');
        }

        if ($this->cacheDir === null) {
            $content = $this->decode($file);

            return self::parseArray($content);
        }

        $ext = \strrchr($file, '.');

        $filename = \basename($file);
        $cache = $this->cacheDir . DIRECTORY_SEPARATOR . \substr($filename, 0, -\strlen($ext)) . '.php';

        if (@\filemtime($cache) < \filemtime($file)) {
            $content = $this->decode($file);

            if (!\is_dir($this->cacheDir) && !@\mkdir($this->cacheDir, 0755) && !\is_dir($this->cacheDir)) {
                throw new \RuntimeException('Cache directory permission denied');
            }

            \file_put_contents($cache, '<?php return ' . self::parseArrayCode($content) . ';');

            if (OPCACHE_INVALIDATE) {
                \opcache_invalidate($cache, true);
            }
        }

        return include $cache;
    }

    private static function parseArray(array $array): array
    {
        foreach ($array as &$v) {
            if (\is_array($v)) {
                $v = self::parseArray($v);
            } else {
                $v = self::parseValue($v);
            }
        }

        return $array;
    }

    private static function parseArrayCode(array $array, int $level = 0): string
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
                $ret .= self::parseArrayCode($v, $level + 1);
            } else {
                $ret .= self::parseValueCode($v);
            }

            $ret .= ',' . "\n";
        }

        return $ret . $pad . ']';
    }

    private static function parseValue($value)
    {
        if (!\is_string($value)) {
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
                $a = self::parseConstant(\trim($a));
            }

            return $function(...$args);
        }

        return self::parseConstant($value);
    }

    /**
     * Parse
     *
     * @param mixed $value
     *
     * @return string
     */
    private static function parseValueCode($value): string
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
                $a = self::parseConstantCode(\trim($a));
            }

            return $function . '(' . \implode(', ', $args) . ')';
        }

        return self::parseConstantCode($value);
    }

    private static function parseConstant(string $value): string
    {
        return \preg_replace_callback('/\${([a-zA-Z0-9_:\\\]+)}/', ['self', 'getConstant'], $value);
    }

    private static function getConstant(array $matches): string
    {
        return \defined($matches[1]) ? $matches[1] : $matches[0];
    }

    private static function parseConstantCode(string $value): string
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
                if (\str_starts_with($value, "'' . ")) {
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
}
