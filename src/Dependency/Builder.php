<?php

namespace zdi\Dependency;

use Closure;
use ReflectionClass;
use ReflectionMethod;

use zdi\ContainerInterface;
use zdi\Container\Builder as ContainerBuilder;
use zdi\Exception;
use zdi\Param\ParamInterface;
use zdi\Param\ClassParam;
use zdi\Param\NamedParam;
use zdi\Param\ValueParam;

class Builder
{
    /**
     * @var ContainerBuilder|null
     */
    private $container;

    /**
     * @var string
     */
    private $class;

    /**
     * @var boolean
     */
    private $factory = false;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $params = array();

    /**
     * @var array
     */
    private $setters = array();

    /**
     * @var Closure
     */
    private $provider;

    /**
     * Builder constructor.
     * @param ContainerBuilder|null $container
     * @param string|null $class
     */
    public function __construct(ContainerBuilder $container = null, $class = null)
    {
        $this->container = $container;
        $this->class = $class;
    }

    /**
     * @param string $alias
     * @return $this
     */
    public function alias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * @return $this
     */
    public function factory()
    {
        $this->factory = true;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $paramName
     * @param string|ParamInterface $containerKey
     * @return $this
     */
    public function param($paramName, $containerKey)
    {
        $this->params[$paramName] = $containerKey;
        return $this;
    }

    /**
     * @param $method
     * @param string|ParamInterface|null $containerKey
     * @return $this
     */
    public function setter($method, $containerKey = null)
    {
        $this->setters[$method] = $containerKey;
        return $this;
    }

    /**
     * @return $this
     */
    public function singleton()
    {
        $this->factory = false;
        return $this;
    }

    /**
     * @param Closure $provider
     * @return $this
     */
    public function using($provider)
    {
        $this->params = null;
        $this->provider = $provider;
        return $this;
    }

    /**
     * @return AbstractDependency
     * @throws Exception\DomainException
     */
    public function build()
    {
        if( is_string($this->provider) ) {
            $dependency = new ProviderDependency($this->class, $this->factory, $this->name, $this->provider);
        } else if( $this->provider instanceof Closure ) {
            $closure = $this->provider;
            $this->validateClosure($closure);
            $dependency = new ClosureDependency($this->class, $this->factory, $this->name, $closure);
        } else if( $this->class ) {
            $reflectionClass = new ReflectionClass($this->class);
            $params = $this->convertParameters($reflectionClass->getConstructor());
            $setters = $this->convertSetters($reflectionClass);
            $dependency = new DefaultDependency($this->class, $this->factory, $this->name, $params, $setters);
        } else {
            throw new Exception\DomainException('Unable to determine dependency type');
        }

        // Add to container
        if( null !== $this->container ) {
            $this->container->add($dependency);
            if( $this->alias ) {
                $this->container->alias($this->alias, $this->class);
            }
        }

        return $dependency;
    }

    /**
     * @param ReflectionMethod|null $reflectionMethod
     * @return ParamInterface[]
     * @throws Exception\DomainException
     */
    private function convertParameters(ReflectionMethod $reflectionMethod = null)
    {
        $result = array();
        if( !$reflectionMethod ) {
            return $result;
        }
        foreach( $reflectionMethod->getParameters() as $parameter ) {
            $position = $parameter->getPosition();
            $name = $parameter->getName();
            $class = $parameter->getClass();
            if( isset($this->params[$name]) ) {
                $result[$position] = $this->convertParam($this->params[$name], $parameter->isOptional());
            } else if( isset($this->params[$position]) ) {
                $result[$position] = $this->convertParam($this->params[$position], $parameter->isOptional());
            } else if( $class ) {
                $result[$position] = new ClassParam($class->name, $parameter->isOptional());
            } else if( $parameter->isDefaultValueAvailable() ) {
                $result[$position] = new ValueParam($parameter->getDefaultValue());
            } else {
                throw new Exception\DomainException(
                    'Unresolved parameter: "' . $name . '" for ' . $this->class ?: $this->name
                );
            }
        }
        return $result;
    }

    /**
     * @param $param
     * @return ParamInterface
     * @throws Exception\DomainException
     */
    private function convertParam($param, $isOptional = false)
    {
        if( is_string($param) ) {
            return new NamedParam($param);
        } else if( $param instanceof ParamInterface ) {
            return $param;
        } else {
            throw new Exception\DomainException("Invalid param");
        }
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @return ParamInterface[]
     * @throws Exception\DomainException
     */
    private function convertSetters(ReflectionClass $reflectionClass)
    {
        $setters = array();
        foreach( $this->setters as $name => $param ) {
            $reflectionMethod = $reflectionClass->getMethod($name);
            $setters[$name] = $this->convertSetter($reflectionMethod, $param);
        }
        return $setters;
    }

    /**
     * @param ReflectionMethod $reflectionMethod
     * @param $param
     * @return ParamInterface[]
     * @throws Exception\DomainException
     */
    private function convertSetter(ReflectionMethod $reflectionMethod, $param)
    {
        $parameters = $reflectionMethod->getParameters();
        $parameter = $parameters[0];
        $class = $parameter->getClass();
        if( $param !== null ) {
            return $this->convertParam($param);
        } else if( $class !== null ) {
            return new ClassParam($class->name);
        } else {
            throw new Exception\DomainException('Unknown setter value');
        }
    }

    /**
     * @param Closure $closure
     * @throws Exception\DomainException
     */
    private function validateClosure(Closure $closure)
    {
        $reflectionFunction = new \ReflectionFunction($closure);
        $nParams = $reflectionFunction->getNumberOfParameters();
        if( $nParams === 0 ) {
            return;
        } else if( $nParams !== 1 ) {
            throw new Exception\DomainException('Closure must have only one or zero parameters');
        }
        $parameters = $reflectionFunction->getParameters();
        $parameter = $parameters[0];
        if( ($class = $parameter->getClass()) ) {
            $interfaceClass = ContainerInterface::class;
            $paramClass = $class->getName();
            if( $paramClass !== $interfaceClass && !is_subclass_of('\\' . $paramClass, $interfaceClass) ) {
                throw new Exception\DomainException('Closure parameter must be zdi\ContainerInterface or a subclass');
            }
        } else {
            throw new Exception\DomainException('Closure provider parameter must have a typehint');
        }
    }
}
