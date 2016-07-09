<?php

namespace zdi\Tests\Fixture;

use zdi\ProviderInterface;

class OneObjectProvider implements ProviderInterface
{
    private $object;

    public function __construct(NoArguments $object)
    {
        $this->object = $object;
    }

    public function get()
    {
        return new OneObjectArgument($this->object);
    }
}
