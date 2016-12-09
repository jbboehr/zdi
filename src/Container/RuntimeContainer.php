<?php

namespace zdi\Container;

use Closure;
use ReflectionClass;
use ReflectionFunction;

use zdi\Container;
use zdi\Definition;
use zdi\Exception;
use zdi\InjectionPoint;
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

    private $stack = array();

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

        // Lookup definition
        $definition = Utils::resolveAliasKey($this->definitions, $key);

        // Make injection point
        $ip = new InjectionPoint();
        $ip->class = $definition->getClass();
        array_push($this->stack, $ip);

        try {
            // Build the parameters
            if ($definition instanceof Definition\DataDefinition) {
                $object = $this->makeDefault($definition, $ip);
            } else if ($definition instanceof Definition\ClosureDefinition) {
                $object = $this->makeClosure($definition, $ip);
            } else if ($definition instanceof Definition\ClassDefinition) {
                $object = $this->makeProvider($definition, $ip);
            } else {
                throw new Exception\DomainException('Unsupported definition: ' . Utils::varInfo($definition));
            }

            // Check for injection interfaces
            if (is_object($object)) {
                $this->injectInterfaces($object, $ip);
            }

            if (!$definition->isFactory()) {
                $this->values[$key] = $object;
            }

            return $object;
        } finally {
            array_pop($this->stack);
        }

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
    public function keys()
    {
        return array_merge(
            array_keys($this->values),
            array_keys($this->definitions)
        );
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

    private function makeDefault(Definition\DataDefinition $definition, InjectionPoint $ip)
    {
        $ip->method = '__construct';

        $class = $definition->getClass();

        $params = array();
        foreach( $definition->getParams() as $position => $param ) {
            $params[$position] = $this->makeParam($param);
        }

        // Build the class
        $reflectionClass = new ReflectionClass($class);
        $object = $reflectionClass->newInstanceArgs($params);

        // Setters
        foreach( $definition->getSetters() as $name => $param ) {
            $ip->method = $name;
            $object->{$name}($this->makeParam($param));
        }

        return $object;
    }


    private function makeClosure(Definition\ClosureDefinition $definition, InjectionPoint $ip)
    {
        $ip->method = null;

        $params = array();
        foreach ($definition->getParams() as $position => $param) {
            $params[$position] = $this->makeParam($param);
        }

        $closure = $definition->getClosure();
        $reflectionFunction = new \ReflectionFunction($closure);
        $object = $reflectionFunction->invokeArgs($params);

        if( $object ) {
            foreach( $definition->getSetters() as $name => $param ) {
                $ip->method = $name;
                $object->{$name}($this->makeParam($param));
            }
        }

        return $object;
    }

    private function makeProvider(Definition\ClassDefinition $definition, InjectionPoint $ip)
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
        } else if( $param instanceof Param\UnresolvedParam ) {
            $definition = Utils::resolveGlobalKey($this->definitions, $param->getName());
            return $this->get($definition->getKey());
        } else if( $param instanceof Param\InjectionPointParam ) {
            if( count($this->stack) < 2 ) {
                return new InjectionPoint();
            } else {
                return $this->stack[count($this->stack) - 2];
            }
        } else {
            throw new Exception\DomainException("Unsupported param: " . Utils::varInfo($param));
        }
    }

    private function injectInterfaces($object, InjectionPoint $ip)
    {
        $r = new \ReflectionClass($object);
        foreach( $r->getInterfaceNames() as $name ) {
            if( !isset($this->definitions[$name]) ) {
                continue;
            }
            $definition = $this->definitions[$name];
            if( !($definition instanceof Definition\InterfaceDefinition) ) {
                continue;
            }
            $setters = $definition->getSetters();
            foreach( $setters as $name => $param ) {
                $ip->method = $name;
                $object->{$name}($this->makeParam($param));
            }
        }
    }
}
