<?php

namespace zdi;

use ArrayAccess;
use Closure;

use zdi\Dependency\AbstractDependency;
use zdi\Dependency\ClosureDependency;
use zdi\Dependency\Dependency;
use zdi\Dependency\DependencyBuilder;
use zdi\Dependency\ProviderDependency;

use zdi\Param\ParamInterface;
use zdi\Param\NamedParam;
use zdi\Param\ClassParam;
use zdi\Param\ValueParam;
use zdi\Param\LookupParam;

class Container extends AbstractContainer
{
    /**
     * @var string[]
     */
    private $aliases = array();

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
     */
    public function __construct($values = array())
    {
        $this->values = $values;
    }

    /**
     * @param AbstractDependency $dependency
     * @return $this
     */
    public function add(AbstractDependency $dependency)
    {
        $this->dependencies[$dependency->getKey()] = $dependency;
        return $this;
    }

    /**
     * @param $interface
     * @param $class
     * @return $this
     */
    public function alias($interface, $class)
    {
        $this->aliases[$interface] = $class;
        return $this;
    }

    /**
     * @param string|null $class
     * @return DependencyBuilder
     */
    public function define($class = null)
    {
        return new DependencyBuilder($this, $class);
    }

    /**
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return isset($this->values[$key])
            || isset($this->aliases[$key])
            || isset($this->dependencies[$key]);
    }

    /**
     * @param string $key
     * @return mixed
     * @throws \Exception
     */
    public function get($key)
    {
        // Already made
        if( isset($this->values[$key]) ) {
            return $this->values[$key];
        }

        // Use an alias
        if( isset($this->aliases[$key]) ) {
            return $this->get($this->aliases[$key]);
            //$class = $this->aliases[$class];
        }

        // Create the dependency if not available
        if( isset($this->dependencies[$key]) ) {
            $dependency = $this->dependencies[$key];
        } else if( is_a($this, $key) ) {
            return $this;
        } else if( class_exists($key, true) ) {
            $dependency = $this->define($key)->build();
        } else {
            throw new \Exception("Not defined");
        }

        // Build the parameters
        if( $dependency instanceof Dependency ) {
            $object = $this->makeDefault($dependency);
        } else if( $dependency instanceof ClosureDependency ) {
            $object = $this->makeClosure($dependency);
        } else if( $dependency instanceof ProviderDependency ) {
            $object = $this->makeProvider($dependency);
        } else {
            throw new \Exception('unknown dependency type');
        }

        if( !$dependency->isFactory() ) {
            $this->values[$key] = $object;
        }

        return $object;

    }

    /**
     * @return AbstractDependency[]
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * @return string[]
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    public function offsetSet($offset, $value)
    {
        if( $value instanceof AbstractDependency ) {
            $this->dependencies[$offset] = $value;
        } else if( $value instanceof \Closure ) {
            $this->define()
                ->name($offset)
                ->using($value)
                ->build();
        } else {
            $this->values[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->aliases[$offset]);
        unset($this->dependencies[$offset]);
        unset($this->values[$offset]);
    }

    private function lookupParam(LookupParam $param)
    {
        $chain = $param->getChain();
        if( !$chain ) {
            return null;
        }
        $ref = $this;
        foreach( $chain as $key ) {
            if( isset($ref[$key]) ) {
                $ref = $ref[$key];
            } else {
                return null;
            }
        }
        return $ref;
    }

    private function makeDefault(Dependency $dependency)
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
        if( $param instanceof LookupParam ) {
            return $this->lookupParam($param);
        } else if( $param instanceof NamedParam ) {
            return $this->get($param->getName());
            //return $this->values[$param->getName()];
        } else if( $param instanceof ClassParam ) {
            return $this->get($param->getClass());
        } else if( $param instanceof ValueParam ) {
            return $param->getValue();
        } else {
            throw new \Exception("Invalid param");
        }
    }
}
