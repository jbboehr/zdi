<?php

namespace zdi\Tests\Fixture;

class OneOptionalObjectArgument
{
    private $object;

    public function __construct(NoArguments $object = null)
    {
        $this->object = $object;
    }

    public function getObject()
    {
        return $this->object;
    }
}
