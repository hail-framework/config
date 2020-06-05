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
        if (!$this->support($file)) {
            throw new \InvalidArgumentException('The file is not supported');
        }

        $json = JsonSerializer::getInstance();

        return $json->decode(
            \file_get_contents($file)
        );
    }
}
