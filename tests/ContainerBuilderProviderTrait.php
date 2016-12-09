<?php

namespace zdi\Tests;

use zdi\Container;
use zdi\Container\ContainerBuilder as Builder;

trait ContainerBuilderProviderTrait
{
    protected function defaultAssertions(Container $container, $class, $key = null)
    {
        $containerKey = $key ?: $class;
        $this->assertInstanceOf($class, $container->get($containerKey));
        $this->assertSame($container->get($containerKey), $container->get($containerKey));
    }

    protected function defaultFactoryAssertions(Container $container, $class, $key = null)
    {
        $containerKey = $key ?: $class;
        $this->assertInstanceOf($class, $container->get($containerKey));
        $this->assertNotSame($container->get($containerKey), $container->get($containerKey));
    }

    public function containerBuilderProvider()
    {
        static $counter;
        // Generate a temp file
        $tmpDir = __DIR__ . '/tmp/';
        $tmpFilePrefix = 'Integration' . sprintf('%02d', ++$counter);
        $tmpFileSuffix = '.php';
        $counter2 = 0;
        do {
            $className = $tmpFilePrefix . ($counter2++ ? '_' . $counter2 : '');
            //$className = $tmpFilePrefix . base_convert(mt_rand(0, PHP_INT_MAX), 10, 36);
            $tmpFile = $tmpDir . $className . $tmpFileSuffix;
        } while( file_exists($tmpFile) );

        // Make the container builders
        $defaultContainerBuilder = new Builder();

        $compileContainerBuilder = new Builder();
        $compileContainerBuilder->ttl(0)
            ->stat(false)
            ->file($tmpFile)
            ->className('zdi\\Tests\\Gen\\' . $className)
        ;

        $precompiledContainerBuilder = clone $compileContainerBuilder;
        $precompiledContainerBuilder->precompiled(true);

        return array(
            array($defaultContainerBuilder),
            array($compileContainerBuilder),
            array($precompiledContainerBuilder),
        );
    }
}
