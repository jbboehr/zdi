<?php

namespace zdi\Param;

use zdi\Param;

class ValueParam implements Param
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
