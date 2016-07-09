<?php

namespace zdi\Tests\Fixture;

class OneSetter
{
    private $object;

    public function setObject(NoArguments $object)
    {
        $this->object = $object;
        return $this;
    }

    public function getObject()
    {
        return $this->object;
    }
}
