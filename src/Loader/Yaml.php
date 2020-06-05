<?php


namespace Hail\Config\Loader;

use Hail\Config\LoaderInterface;
use Hail\Serializer\Yaml as YamlSerializer;

class Yaml implements LoaderInterface
{
    use Traits\TextLoader;

    public function ext(): array
    {
        return ['.yml', '.yaml'];
    }

    protected function decode(string $file): array
    {
        if (!$this->support($file)) {
            throw new \InvalidArgumentException('The file is not supported');
        }

        $yaml = YamlSerializer::getInstance();

        return $yaml->decode(
            \file_get_contents($file)
        );
    }
}
