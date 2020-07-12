<?php


namespace Hail\Config\Loader;

use Hail\Config\LoaderInterface;

class Ini implements LoaderInterface
{
    use Traits\TextLoader;

    public function ext(): array
    {
        return ['.ini'];
    }

    protected function decode(string $file): array
    {
        if (!$this->support($file)) {
            throw new \InvalidArgumentException('The file is not supported');
        }

        return \parse_ini_file($file, false, INI_SCANNER_TYPED);
    }
}
