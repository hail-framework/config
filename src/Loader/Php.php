<?php

namespace Hail\Config\Loader;

use Hail\Config\LoaderInterface;

class Php implements LoaderInterface
{
    use Traits\Loader;

    public function ext(): array
    {
        return ['.php'];
    }

    public function load(string $file): array
    {
        if (!$this->support($file)) {
            throw new \InvalidArgumentException('The file is not supported');
        }

        return include $file;
    }
}
