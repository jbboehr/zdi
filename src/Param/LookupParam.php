<?php

namespace zdi\Param;

class LookupParam implements ParamInterface
{
    private $chain;

    private $isObject;

    public function __construct(array $chain, $isObject = false)
    {
        $this->chain = $chain;
        $this->isObject = $isObject;
    }

    public function getChain()
    {
        return $this->chain;
    }

    public function isObject()
    {
        return $this->isObject;
    }
}
