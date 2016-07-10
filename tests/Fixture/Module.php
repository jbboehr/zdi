<?php

namespace zdi\Tests\Fixture;

use zdi\Container\ContainerBuilder;

class Module implements \zdi\Module
{
    public function define(ContainerBuilder $builder)
    {
        $builder->define(NoArguments::class)
            ->build();
        $builder->define(OneObjectArgument::class)
            ->build();
    }
}
