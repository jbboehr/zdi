<?php

namespace zdi\Tests\Dependency;

use zdi\Container\CompileBuilder;
use zdi\Container\DefaultBuilder;
use zdi\Container\PrecompiledBuilder;
use zdi\Tests\Fixture;
use zdi\Container\Builder;

class BuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultBuilderIsNeverReady()
    {
        $builder = new DefaultBuilder();
        $this->configureBuilder($builder);
        $this->assertFalse($builder->isReady());
    }

    public function testCompileBuilderStat()
    {
        $builder = $this->getCompileBuilder();
        $builder->stat(true);

        // The builder should be 'invalid' as the file hasn't been generated yet
        $this->assertFalse($builder->isValid());

        // Generate the file
        $container = $builder->build();

        // The builder should be 'valid' as the container file is newer than any dependencies
        $this->assertTrue($builder->isValid());

        // touch the container to before a dependency, then it should be invalid
        $r1 = new \ReflectionClass(Fixture\NoArguments::class);
        $r2 = new \ReflectionClass($container);
        $orig = filemtime($r2->getFileName());
        touch($r2->getFileName(), filemtime($r1->getFileName()) - 1);
        $this->assertFalse($builder->isValid());
        // @todo revert this on failure?
        touch($r2->getFileName(), $orig);
    }

    public function testCompileBuilderTtl()
    {
        $builder = $this->getCompileBuilder();

        // The builder should be 'unready' as the file hasn't been generated yet
        $builder->ttl(3600);
        $this->assertFalse($builder->isReady());

        // -1 disables ttl checking (always ready)
        $builder->ttl(-1);
        $this->assertTrue($builder->isReady());

        // 0 disables ttl checking (never ready)
        $builder->ttl(0);
        $this->assertFalse($builder->isReady());

        // Build the container
        $container = $builder->build();

        // Set a TTL and check if it's 'ready'
        $builder->ttl(3600);
        $this->assertTrue($builder->isReady());

        // Touch the file, is should be 'unready'
        $r = new \ReflectionClass($container);
        $builder->ttl(1200);
        $orig = filemtime($r->getFileName());
        touch($r->getFileName(), $orig - 3600);
        $this->assertFalse($builder->isReady());
        touch($r->getFileName(), $orig);
    }

    public function testPrecompiledBuilder()
    {
        $builder1 = $this->getCompileBuilder();
        $container = $builder1->build();

        $r = new \ReflectionClass($container);
        $class = $r->getName();
        $pos = strrpos($class, '\\');
        $namespace = substr($class, 0, $pos);
        $class = substr($class, $pos + 1);

        $builder2 = new PrecompiledBuilder($r->getFileName(), $namespace, $class);

        // Should always be 'ready'
        $this->assertTrue($builder2->isReady());

        // Build the container againt
        $container2 = $builder2->build();

        // Check if it works
        $this->assertInstanceOf(Fixture\NoArguments::class, $container2->get(Fixture\NoArguments::class));
    }

    private function getCompileBuilder()
    {
        list($class, $tmpFile) = $this->mktmp();
        $namespace = 'zdi\\Tests\\Gen';
        $builder = new CompileBuilder($tmpFile, $namespace, $class);
        $this->configureBuilder($builder);
        return $builder;
    }


    private function configureBuilder(Builder $builder)
    {
        $builder->define(Fixture\NoArguments::class)
            ->build();

    }

    private function mktmp()
    {
        // Generate a temp file
        $tmpDir = realpath(__DIR__ . '/../tmp') . '/';
        $tmpFilePrefix = 'zdiContainerBuilderTest_';
        $tmpFileSuffix = '.tmp.php';
        do {
            $className = $tmpFilePrefix . base_convert(mt_rand(0, PHP_INT_MAX), 10, 36);
            $tmpFile = $tmpDir . $className . $tmpFileSuffix;
        } while( file_exists($tmpFile) );
        return array($className, $tmpFile);
    }
}
