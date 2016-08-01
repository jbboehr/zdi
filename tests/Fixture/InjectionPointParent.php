<?php

namespace zdi\Tests\Fixture;

class InjectionPointParent implements InjectionPointChildAwareInterface
{
    private $str1;
    private $str2;

    public function __construct(InjectionPointChild $child)
    {
        $this->str1 = $child->getStr();
    }

    public function setInjectionPointChild(InjectionPointChild $child)
    {
        $this->str2 = $child->getStr();
    }

    public function getStr1()
    {
        return $this->str1;
    }

    public function getStr2()
    {
        return $this->str2;
    }
}
