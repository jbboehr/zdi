<?php

namespace zdi\Tests\Fixture;

class OneScalarArgument
{
    private $str;

    public function __construct($str)
    {
        $this->str = $str;
    }

    public function getString()
    {
        return $this->str;
    }
}
