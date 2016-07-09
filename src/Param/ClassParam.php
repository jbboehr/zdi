<?php

namespace zdi\Param;

use zdi\Utils;

class ClassParam extends AbstractParam
{
    /**
     * @var string
     */
    private $class;

    /**
     * ClassParam constructor.
     * @param string $class
     * @param boolean $isOptional
     */
    public function __construct($class, $isOptional = false)
    {
        $this->class = $class;
        parent::__construct($isOptional);
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
