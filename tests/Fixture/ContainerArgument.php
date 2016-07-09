<?php

namespace zdi\Tests\Fixture;

use zdi\Container;

class ContainerArgument
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }
}
