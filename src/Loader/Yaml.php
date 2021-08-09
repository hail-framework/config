<?php


namespace Hail\Config\Loader;

use Hail\Config\LoaderInterface;
use Symfony\Component\Yaml\Yaml as SYaml;

\defined('YAML_EXTENSION') || \define('YAML_EXTENSION', \extension_loaded('yaml'));

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

        if (YAML_EXTENSION) {
            return \yaml_parse_file($file);
        }

        if (!\class_exists(SYaml::class)) {
            throw new \RuntimeException('"symfony/yaml" is required to parse YAML files.');
        }

        return SYaml::parseFile($file);
    }
}
