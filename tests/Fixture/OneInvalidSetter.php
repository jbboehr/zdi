<?php

namespace zdi\Tests\Fixture;

class OneInvalidSetter
{
    private $object;

    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    public function getObject()
    {
        return $this->object;
    }
}
