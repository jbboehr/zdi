<?php

namespace zdi\Container;

use Closure;
use ReflectionClass;
use ReflectionFunction;

use zdi\Container;
use zdi\Definition;
use zdi\Exception;
use zdi\Param;
use zdi\Utils;

class RuntimeContainer implements Container
{
    /**
     * @var Definition[]
     */
    private $definitions = array();

    /**
     * @var array
     */
    private $values = array();

    /**
     * @param array $values
     * @param array $definitions
     */
    public function __construct(array $values = array(), array $definitions = array())
    {
        $this->values = $values;
        $this->definitions = $definitions;
    }

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        // Already made
        if( isset($this->values[$key]) ) {
            return $this->values[$key];
        }

        // Create the definition if not available
        if( isset($this->definitions[$key]) ) {
            $definition = $this->definitions[$key];
        // @todo re-enable dynamic resolution
        } else {
            throw new Exception\OutOfBoundsException("Undefined identifier: " . $key);
        }

        // Build the parameters
        if( $definition instanceof Definition\DataDefinition ) {
            $object = $this->makeDefault($definition);
        } else if( $definition instanceof Definition\ClosureDefinition ) {
            $object = $this->makeClosure($definition);
        } else if( $definition instanceof Definition\AliasDefinition ) {
            return $this->get($definition->getAlias());
        } else if( $definition instanceof Definition\ClassDefinition ) {
            $object = $this->makeProvider($definition);
        } else {
            throw new Exception\DomainException('Unsupported definition: ' . Utils::varInfo($definition));
        }

        if( !$definition->isFactory() ) {
            $this->values[$key] = $object;
        }

        return $object;
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        return isset($this->values[$key]) || isset($this->definitions[$key]);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $this->values[$offset] = $value;
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->definitions[$offset]);
        unset($this->values[$offset]);
    }

    private function makeDefault(Definition\DataDefinition $definition)
    {
        $class = $definition->getClass();

        $params = array();
        foreach( $definition->getParams() as $position => $param ) {
            $params[$position] = $this->makeParam($param);
        }

        // Build the class
        $reflectionClass = new ReflectionClass($class);
        $object = $reflectionClass->newInstanceArgs($params);

        // Do setters
        foreach( $definition->getSetters() as $name => $param ) {
            $object->{$name}($this->makeParam($param));
        }

        return $object;
    }

    private function makeClosure(Definition\ClosureDefinition $definition)
    {
        $params = array();
        foreach( $definition->getParams() as $position => $param ) {
            $params[$position] = $this->makeParam($param);
        }

        $closure = $definition->getClosure();
        $reflectionFunction = new \ReflectionFunction($closure);
        return $reflectionFunction->invokeArgs($params);
    }

    private function makeProvider(Definition\ClassDefinition $definition)
    {
        $provider = $this->get($definition->getProvider());
        return $provider->get();
    }

    private function makeParam(Param $param)
    {
        if( $param instanceof Param\NamedParam ) {
            return $this->get($param->getName());
        } else if( $param instanceof Param\ClassParam ) {
            if( $param->isOptional() && !$this->has($param->getClass()) ) {
                return null;
            } else if( is_a($this, $param->getClass()) ) {
                return $this;
            } else {
                return $this->get($param->getClass());
            }
        } else if( $param instanceof Param\ValueParam ) {
            return $param->getValue();
        } else {
            throw new Exception\DomainException("Unsupported param: " . Utils::varInfo($param));
        }
    }

}
