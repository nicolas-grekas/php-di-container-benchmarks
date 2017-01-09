<?php
declare(strict_types=1);

namespace DiContainerBenchmarks\Container\Yaco;

use DiContainerBenchmarks\Test\UnsupportedFeatureException;

class Test1 extends AbstractYacoTest
{
    public function startup(): void
    {
    }

    public function run(): void
    {
    }

    public function supported(): bool
    {
        throw new UnsupportedFeatureException('Yaco cannot act as a factory.');
    }
}
