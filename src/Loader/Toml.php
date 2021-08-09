<?php


namespace Hail\Config\Loader;

use Hail\Config\LoaderInterface;
use Yosymfony\Toml\Toml as YToml;

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

        if (!\class_exists(YToml::class)) {
            throw new \RuntimeException('"yosymfony/toml" is required to parse TOML files.');
        }

        return YToml::parseFile($file);
    }
}
