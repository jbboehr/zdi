<?php

namespace zdi\Tests\Definition;

use zdi\Container\ContainerBuilder;
use zdi\Tests\Fixture;

class BuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testCompileBuilderNeedsRebuild()
    {
        $builder = $this->getBuilder();
        $builder->stat(true);

        // The builder should need rebuild as the file doesn't exist yet
        $this->assertTrue($builder->needsRebuild());

        // Generate the file
        $container = $builder->build();

        // The builder always not need rebuild as the container file is newer than any definitions
        $this->assertFalse($builder->needsRebuild());

        // touch the container to before a definition, then it should be need rebuild again
        $r1 = new \ReflectionClass(Fixture\NoArguments::class);
        $r2 = new \ReflectionClass($container);
        $orig = filemtime($r2->getFileName());
        touch($r2->getFileName(), filemtime($r1->getFileName()) - 1);
        $this->assertTrue($builder->needsRebuild());
        // @todo revert this on failure?
        touch($r2->getFileName(), $orig);

        // Dynamic always requires rebuild
        $builder2 = (new ContainerBuilder());
        $this->assertTrue($builder2->needsRebuild());
    }

    public function testCompileBuilderNeedsRedefine()
    {
        $builder = $this->getBuilder();

        // The builder should need redefine as the file hasn't been generated yet
        $builder->ttl(3600);
        $this->assertTrue($builder->needsRedefine());

        // -1 disables ttl checking (never needs redefine)
        $builder->ttl(-1);
        $this->assertFalse($builder->needsRedefine());

        // 0 disables ttl checking (always needs redefine)
        $builder->ttl(0);
        $this->assertTrue($builder->needsRedefine());

        // Build the container
        $container = $builder->build();

        // Set a TTL and check if it needs redefine
        $builder->ttl(3600);
        $this->assertFalse($builder->needsRedefine());

        // Touch the file, it should need redefine
        $r = new \ReflectionClass($container);
        $builder->ttl(1200);
        $orig = filemtime($r->getFileName());
        touch($r->getFileName(), $orig - 3600);
        $this->assertTrue($builder->needsRedefine());
        touch($r->getFileName(), $orig);

        // Precompiled never needs redefine
        $builder->precompiled(true);
        $this->assertFalse($builder->needsRedefine());

        // Dynamic always requires redefine
        $builder2 = (new ContainerBuilder());
        $this->assertTrue($builder2->needsRedefine());
    }

    private function getBuilder()
    {
        list($class, $tmpFile) = $this->mktmp();
        $namespace = 'zdi\\Tests\\Gen';
        $builder = new ContainerBuilder();
        $builder->file($tmpFile);
        $builder->className($namespace . '\\' . $class);
        $builder->define(Fixture\NoArguments::class)
            ->build();
        return $builder;
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
