<?php

namespace Hail\Config\Loader;

use Hail\Config\LoaderInterface;

class Json implements LoaderInterface
{
    use Traits\TextLoader;

    public function ext(): array
    {
        return ['.json'];
    }

    protected function decode(string $file): array
    {
        if (!$this->support($file)) {
            throw new \InvalidArgumentException('The file is not supported');
        }

        return \json_decode(
            \file_get_contents($file), true
        );
    }
}
