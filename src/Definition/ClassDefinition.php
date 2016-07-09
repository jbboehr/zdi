<?php

namespace zdi\Definition;

class ClassDefinition extends AbstractDefinition
{
    /**
     * @var string
     */
    private $provider;

    /**
     * @param $class
     * @param bool $factory
     * @param null|string $name
     * @param $provider
     */
    public function __construct($class, $factory, $name, $provider)
    {
        parent::__construct($class, $factory, $name);
        $this->provider = $provider;
    }

    /**
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }
}
