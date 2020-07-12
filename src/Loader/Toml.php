<?php


namespace Hail\Config\Loader;

use Hail\Config\LoaderInterface;

class Toml implements LoaderInterface
{
    use Traits\TextLoader;

    public function ext(): array
    {
        return ['.toml'];
    }

    protected function decode(string $file): array
    {
        if (!$this->support($file)) {
            throw new \InvalidArgumentException('The file is not supported');
        }

        if (YAML_EXTENSION) {
            return \yaml_parse_file($file);
        }

        if (!\class_exists(\Yosymfony\Toml\Toml::class)) {
            throw new \RuntimeException('"yosymfony/toml" is required to parse TOML files.');
        }

        return \Yosymfony\Toml\Toml::parseFile($file);
    }
}
