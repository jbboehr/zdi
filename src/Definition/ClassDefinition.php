<?php

namespace zdi\Definition;

class ClassDefinition extends AbstractDefinition
{
    /**
     * @var string
     */
    private $provider;

    /**
     * @param string $provider
     * @param string $class
     * @param null|string $name
     * @param integer $flags
     */
    public function __construct($provider, $class, $name, $flags)
    {
        $this->provider = $provider;
        parent::__construct($class, $name, $flags);
    }

    /**
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }
}
