<?php

namespace zdi\Tests\Fixture;

use zdi\Provider;

class OneObjectProvider implements Provider
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
