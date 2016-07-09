<?php

namespace zdi\Tests\Fixture;

use zdi\ContainerInterface;

class ContainerArgument
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }
}
