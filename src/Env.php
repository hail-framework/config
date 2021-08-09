<?php

namespace Hail\Config;

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
    private array $loaded = [];

    protected array $names = [];

    public function __construct(string $path)
    {
        if (!\is_dir($path)) {
            throw new \InvalidArgumentException("'$path' is not exists");
        }

        $this->load($path . DIRECTORY_SEPARATOR . '.env');
    }

    public function load(string $file): self
    {
        if (!\is_file($file) || !\is_readable($file)) {
            return $this;
        }

        $array = \parse_ini_file($file, scanner_mode: INI_SCANNER_TYPED);

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

        $this->names[] = $name;
        $value = \trim($value);

        // If PHP is running as an Apache module and an existing
        // Apache environment variable exists, overwrite it
        if (
            FUNCTION_APACHE_SETENV &&
            FUNCTION_APACHE_GETENV &&
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
        if (FUNCTION_PUTENV) {
            \putenv($name);
        }

        unset($_ENV[$name], $_SERVER[$name]);

        return $this;
    }

    public function reset(): self
    {
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
