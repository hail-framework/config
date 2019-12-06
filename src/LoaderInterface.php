<?php


namespace Hail\Config;


interface LoaderInterface
{
    public function ext(): array;

    public function find(string $file): ?string;

    public function load(string $file): array;
}
