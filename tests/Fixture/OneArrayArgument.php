<?php

namespace zdi\Tests\Fixture;

class OneArrayArgument
{
    private $arr;

    public function __construct(array $arr)
    {
        $this->arr = $arr;
    }

    public function getArray()
    {
        return $this->arr;
    }
}
