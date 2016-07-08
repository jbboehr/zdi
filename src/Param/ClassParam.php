<?php

namespace zdi\Param;

use zdi\Utils;

class ClassParam implements ParamInterface
{
    /**
     * @var string
     */
    private $class;

    /**
     * ClassParam constructor.
     * @param string $class
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return Utils::classToIdentifier($this->class);
    }
}
