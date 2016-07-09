<?php

namespace zdi\Tests\Fixture;

class RecursiveObjectArguments
{
    private $object1;

    private $object2;

    public function __construct(OneObjectArgument $object1, OneOptionalObjectArgument $object2)
    {
        $this->object1 = $object1;
        $this->object2 = $object2;
    }

    public function getObject1()
    {
        return $this->object1;
    }

    public function getObject2()
    {
        return $this->object2;
    }
}
