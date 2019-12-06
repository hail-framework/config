<?php

namespace Hail\Config\Loader\Traits;

trait Loader
{
    abstract public function ext(): array;

    protected function support(string $file): bool
    {
        $ext = \strrchr($file, '.');

        if (!\in_array($ext, $this->ext(), true)) {
            return false;
        }

        return \is_file($file);
    }

    public function find(string $file): ?string
    {
        foreach ($this->ext() as $ext) {
            $filename = $file . $ext;
            if (\is_file($filename)) {
                return $filename;
            }
        }

        return null;
    }
}
