<?php

namespace Hail\Config;

use Hail\Config\Loader\Ini;

\defined('FUNCTION_PUTENV') || \define('FUNCTION_PUTENV', \function_exists('\putenv'));
\defined('FUNCTION_APACHE_SETENV') || \define('FUNCTION_APACHE_SETENV', \function_exists('\apache_setenv'));
\defined('FUNCTION_APACHE_GETENV') || \define('FUNCTION_APACHE_GETENV', \function_exists('\apache_getenv'));

/**
 * Class Env
 * setting the environment vars
 *
 * @package Hail\Config
 */
class Env
{
    public const FILE = '.env';

    private $loaded = [];

    protected $immutable = false;

    protected $names = [];

    public function __construct($files)
    {
        $files = (array) $files;

        foreach ($files as $v) {
            if (\is_dir($v)) {
                $v .= DIRECTORY_SEPARATOR . self::FILE;
            }

            $this->load($v);
        }
    }

    public function withImmutable(): self
    {
        $new = clone $this;
        $new->immutable = true;

        return $new;
    }

    public function isImmutable(): bool
    {
        return $this->immutable;
    }

    public function load(string $file): self
    {
        if (!\is_file($file) || !\is_readable($file)) {
            return $this;
        }

        $array = \parse_ini_file($file, false, INI_SCANNER_TYPED);

        foreach ($array as $name => $value) {
            $this->set($name, $value);
        }

        $this->loaded[] = $file;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function get(string $name): ?string
    {
        return static::getenv($name);
    }

    public static function getenv(string $name): ?string
    {
        if (isset($_ENV[$name])) {
            return $_ENV[$name];
        }

        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }

        $value = \getenv($name);

        return $value === false ? null : $value;
    }

    protected function set(string $name, string $value): void
    {
        $name = \trim($name);

        if ($this->immutable && $this->get($name) !== null) {
            return;
        }

        $this->names[] = $name;
        $value = \trim($value);

        // If PHP is running as an Apache module and an existing
        // Apache environment variable exists, overwrite it
        if (
            FUNCTION_APACHE_SETENV && FUNCTION_APACHE_GETENV &&
            \apache_getenv($name) !== false
        ) {
            \apache_setenv($name, $value);
        }

        if (FUNCTION_PUTENV) {
            \putenv("$name=$value");
        }

        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    public function clear($name): self
    {
        if ($this->immutable) {
            return $this;
        }

        if (FUNCTION_PUTENV) {
            \putenv($name);
        }

        unset($_ENV[$name], $_SERVER[$name]);

        return $this;
    }

    public function reset(): self
    {
        if ($this->immutable) {
            return $this;
        }

        $old = $this->names;
        $loaded = \array_unique($this->loaded);
        $this->loaded = $this->names = [];
        foreach ($loaded as $file) {
            $this->load($file);
        }

        $diff = \array_diff($old, $this->names);
        foreach ($diff as $v) {
            $this->clear($v);
        }

        return $this;
    }
}
