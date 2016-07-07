<?php

namespace zdi\Param;

use zdi\Utils;

class ClassParam implements ParamInterface
{
    private $class;

    public function __construct($class)
    {
        $this->class = $class;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getIdentifier()
    {
        return Utils::classToIdentifier($this->class);
    }
}
