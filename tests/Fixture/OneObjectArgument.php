<?php

namespace zdi\Tests\Fixture;

class OneObjectArgument
{
    private $object;

    public function __construct(NoArguments $object)
    {
        $this->object = $object;
    }

    public function getObject()
    {
        return $this->object;
    }
}
