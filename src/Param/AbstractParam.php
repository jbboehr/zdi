<?php

namespace zdi\Param;

class AbstractParam implements ParamInterface
{
    private $isOptional = false;
    
    public function __construct($isOptional = false)
    {
        $this->isOptional = $isOptional;
    }
    
    public function isOptional()
    {
        return $this->isOptional;
    }
}
