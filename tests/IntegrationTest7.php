<?php

namespace zdi\Tests;

use zdi\Container\ContainerBuilder as Builder;

class IntegrationTest7 extends IntegrationTest5
{
    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testClosureReturnTypeDeclaration(Builder $builder)
    {
        $builder->define()
            ->using(static function() : Fixture\OneObjectArgument {
                $obj = new Fixture\NoArguments();
                return new Fixture\OneObjectArgument($obj);
            })
            ->build();
        $container = $builder->build();

        $this->defaultAssertions($container, Fixture\OneObjectArgument::class);
        $this->assertInstanceOf(Fixture\NoArguments::class, $container->get(Fixture\OneObjectArgument::class)->getObject());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testClosureScalarReturnTypeDeclaration(Builder $builder)
    {
        $builder->define()
            ->name('foo')
            ->using(static function() : string {
                return 'bar';
            })
            ->build();
        $container = $builder->build();

        $this->assertSame('bar', $container->get('foo'));
    }
}
