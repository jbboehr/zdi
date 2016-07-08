<?php

namespace zdi\Param;

class ValueParam implements ParamInterface
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * ValueParam constructor.
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
