<?php

namespace zdi\Tests\Fixture;

class DefaultValueArgument
{
    const DEFAULT_VALUE = 'this is the default value';
    private $str;
    
    public function __construct($str = self::DEFAULT_VALUE)
    {
        $this->str = $str;
    }

    public function getString()
    {
        return $this->str;
    }
}
