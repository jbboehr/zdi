<?php

namespace zdi\Definition;

use zdi\Exception;
use zdi\Param;

class DataDefinition extends AbstractDefinition
{
    /**
     * @var Param[]
     */
    private $params;

    /**
     * @var Param[]
     */
    private $setters;

    /**
     * @param array $params
     * @param array $setters
     * @param string $class
     * @param string $name
     * @param integer $flags
     */
    public function __construct(array $params, array $setters, $class, $name, $flags)
    {
        $this->params = $params;
        $this->setters = $setters;

        parent::__construct($class, $name, $flags);

        if( $this->hasInjectionPointParam() && !$this->isFactory() ) {
            throw new Exception\DomainException('Definition with injection point must be marked as factory');
        }
    }

    /**
     * @return Param[]
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return Param[]
     */
    public function getSetters()
    {
        return $this->setters;
    }

    /**
     * @return boolean
     */
    public function hasInjectionPointParam()
    {
        foreach( $this->params as $param ) {
            if( $param instanceof Param\InjectionPointParam ) {
                return true;
            }
        }
        foreach( $this->setters as $param ) {
            if( $param instanceof Param\InjectionPointParam ) {
                return true;
            }
        }
        return false;
    }
}
