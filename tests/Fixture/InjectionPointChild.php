<?php

namespace zdi\Tests\Fixture;

class InjectionPointChild
{
    private $str;

    public function __construct($str)
    {
        $this->str = $str;
    }

    public function getStr()
    {
        return $this->str;
    }
}
