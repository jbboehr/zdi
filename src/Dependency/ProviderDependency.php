<?php

namespace zdi\Dependency;

class ProviderDependency extends AbstractDependency
{
    /**
     * @var string
     */
    private $provider;

    /**
     * ProviderDependency constructor.
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
