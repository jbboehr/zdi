<?php

namespace zdi;

use Closure;

use zdi\Dependency\AliasDependency;
use zdi\Dependency\AbstractDependency;
use zdi\Dependency\ClosureDependency;
use zdi\Dependency\DefaultDependency;
use zdi\Dependency\ProviderDependency;

use zdi\Param\ParamInterface;
use zdi\Param\NamedParam;
use zdi\Param\ClassParam;
use zdi\Param\ValueParam;

class Container implements ContainerInterface
{
    /**
     * @var AbstractDependency[]
     */
    private $dependencies = array();

    /**
     * @var array
     */
    private $values = array();

    /**
     * Container constructor.
     * @param array $values
     * @param array $dependencies
     */
    public function __construct(array $values = array(), array $dependencies = array())
    {
        $this->values = $values;
        $this->dependencies = $dependencies;
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

        // Create the dependency if not available
        if( isset($this->dependencies[$key]) ) {
            $dependency = $this->dependencies[$key];
// @todo re-enable this
//        } else if( class_exists($key, true) ) {
//            $builder = new Dependency\Builder(null, $key);
//            $dependency = $builder->build();
        } else {
            throw new Exception\OutOfBoundsException("Undefined identifier: " . $key);
        }

        // Build the parameters
        if( $dependency instanceof DefaultDependency ) {
            $object = $this->makeDefault($dependency);
        } else if( $dependency instanceof ClosureDependency ) {
            $object = $this->makeClosure($dependency);
        } else if( $dependency instanceof ProviderDependency ) {
            $object = $this->makeProvider($dependency);
        } else if( $dependency instanceof AliasDependency ) {
            return $this->get($dependency->getAlias());
        } else {
            throw new Exception\DomainException('Unsupported dependency: ' . Utils::varInfo($dependency));
        }

        if( !$dependency->isFactory() ) {
            $this->values[$key] = $object;
        }

        return $object;
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        return isset($this->values[$key]) || isset($this->dependencies[$key]);
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
        unset($this->dependencies[$offset]);
        unset($this->values[$offset]);
    }

    private function makeDefault(DefaultDependency $dependency)
    {
        $class = $dependency->getClass();

        $params = array();
        foreach( $dependency->getParams() as $position => $param ) {
            $params[$position] = $this->makeParam($param);
        }

        // Build the class
        $reflectionClass = new \ReflectionClass($class);
        $object = $reflectionClass->newInstanceArgs($params);

        // Do setters
        foreach( $dependency->getSetters() as $name => $param ) {
            $object->{$name}($this->makeParam($param));
        }

        return $object;
    }

    private function makeClosure(ClosureDependency $dependency)
    {
        $closure = $dependency->getClosure();
        return $closure($this);
    }

    private function makeProvider(ProviderDependency $dependency)
    {
        $provider = $this->get($dependency->getProvider());
        return $provider->get();
    }

    private function makeParam(ParamInterface $param)
    {
        if( $param instanceof NamedParam ) {
            return $this->get($param->getName());
        } else if( $param instanceof ClassParam ) {
            if( $param->isOptional() && !$this->has($param->getClass()) ) {
                return null;
            } else {
                return $this->get($param->getClass());
            }
        } else if( $param instanceof ValueParam ) {
            return $param->getValue();
        } else {
            throw new Exception\DomainException("Unsupported param: " . Utils::varInfo($param));
        }
    }

}
