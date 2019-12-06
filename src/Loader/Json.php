<?php

namespace Hail\Config\Loader;

use Hail\Config\LoaderInterface;
use Hail\Serializer\Json as JsonSerializer;

class Json implements LoaderInterface
{
    use Traits\TextLoader;

    public function ext(): array
    {
        return ['.json'];
    }

    protected function decode(string $file): array
    {
        $json = JsonSerializer::getInstance();

        return $json->decode(
            \file_get_contents($file)
        );
    }
}
