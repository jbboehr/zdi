<?php

namespace zdi\Tests\Container;

use zdi\Tests\Fixture;
use zdi\Definition\DefinitionBuilder;
use zdi\Exception\DomainException;

class BuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testInvalidOptions()
    {
        $this->setExpectedException(DomainException::class);
        $builder = new DefinitionBuilder();
        $builder->build();
    }

    public function testSingleton()
    {
        $builder = new DefinitionBuilder(null, Fixture\NoArguments::class);
        $builder->factory();
        $dep1 = $builder->build();
        $this->assertTrue($dep1->isFactory());
        $builder->singleton();
        $dep2 = $builder->build();
        $this->assertFalse($dep2->isFactory());
    }
}