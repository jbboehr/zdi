<?php

namespace zdi\Compiler\DefinitionCompiler;

use PhpParser\BuilderFactory;
use PhpParser\Node;

use zdi\Compiler\DefinitionCompiler;
use zdi\Container;
use zdi\Definition;
use zdi\Exception;
use zdi\InjectionPoint;
use zdi\Param;
use zdi\Utils;

abstract class AbstractDefinitionCompiler implements DefinitionCompiler
{
    /**
     * @var BuilderFactory
     */
    protected $builderFactory;

    /**
     * @var Definition
     */
    protected $definition;

    /**
     * @var Definition[]
     */
    protected $definitions;

    /**
     * @var \ArrayAccess|array
     */
    protected $astCache;

    /**
     * @param BuilderFactory $builderFactory
     * @param Definition $definition
     * @param Definition[] $definitions
     */
    final public function __construct(BuilderFactory $builderFactory, Definition $definition, array $definitions, $astCache)
    {
        $this->builderFactory = $builderFactory;
        $this->definition = $definition;
        $this->definitions = $definitions;
        $this->astCache = $astCache;
    }

    /**
     * @return Node\Stmt\If_
     */
    protected function makeSingletonCheck()
    {
        $prop = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $this->definition->getIdentifier());
        return new Node\Stmt\If_(
            new Node\Expr\BinaryOp\NotIdentical(
                new Node\Expr\ConstFetch(new Node\Name('null')),
                clone $prop
            ),
            array('stmts' => array(new Node\Stmt\Return_(clone $prop)))
        );
    }

    /**
     * @param Param $param
     * @param InjectionPoint $ip
     * @return Node\Expr
     * @throws Exception\DomainException
     */
    protected function compileParam(Param $param, InjectionPoint $ip)
    {
        if( $param instanceof Param\ClassParam ) {
            $key = $param->getClass();
            // Just return this if it's asking for a container
            if( $key == Container::class ) {
                return new Node\Expr\Variable('this');
            }
            // Get definition
            $definition = Utils::resolveAliasKey($this->definitions, $key, $param->isOptional());
            if( $definition ) {
                return $this->compileParamInjectionPoint($definition, $ip);
            } else {
                return new Node\Expr\ConstFetch(new Node\Name('null'));
            }
        } else if( $param instanceof Param\NamedParam ) {
            $definition = Utils::resolveAliasKey($this->definitions, $param->getName(), true);
            if( $definition ) {
                return $this->compileParamInjectionPoint($definition, $ip);
            } else {
                return new Node\Expr\MethodCall(new Node\Expr\Variable('this'), 'get', array(
                    new Node\Arg(new Node\Scalar\String_($param->getName()))
                ));
            }
        } else if( $param instanceof Param\ValueParam ) {
            return new Node\Arg($this->compileValue($param->getValue()));
        } else if( $param instanceof Param\UnresolvedParam ) {
            $definition = Utils::resolveGlobalKey($this->definitions, $param->getName(), true);
            if (!$definition) {
                throw new Exception\OutOfBoundsException("Undefined identifier: " . $param->getName() . ' for definition: ' . $this->definition->getKey());
            }
            return $this->compileParamInjectionPoint($definition, $ip);
        } else if( $param instanceof Param\InjectionPointParam ) {

            return new Node\Expr\ConstFetch(new Node\Name('WHOOPSIES'));
        } else {
            throw new Exception\DomainException('Unsupported parameter: ' . Utils::varInfo($param) . ' for definition: ' . $this->definition->getKey());
        }
    }

    /**
     * @param Definition $definition
     * @param InjectionPoint $ip
     * @return Node\Expr\MethodCall
     */
    private function compileParamInjectionPoint(Definition $definition, InjectionPoint $ip)
    {
        $hasInjectionPoint = false;
        if( $definition instanceof Definition\ClosureDefinition ) {
            $hasInjectionPoint = $definition->hasInjectionPointParam();
        } else if( $definition instanceof Definition\DataDefinition ) {
            $hasInjectionPoint = $definition->hasInjectionPointParam();
        }
        $args = array();
        if( $hasInjectionPoint ) {
            $args[] = new Node\Arg(new Node\Scalar\String_($ip->class));
            if( $ip->method ) {
                $args[] = new Node\Arg(new Node\Scalar\String_($ip->method));
            }
        }
        return new Node\Expr\MethodCall(new Node\Expr\Variable('this'), $definition->getIdentifier(), $args);
    }

    /**
     * @param mixed $value
     * @return Node\Expr
     * @throws Exception\DomainException
     */
    protected function compileValue($value)
    {
        return Utils::parserNodeFromValue($value);
    }

    /**
     * @param Param[] $setters
     * @param Node\Expr $var
     * @return Node\Stmt[]
     */
    protected function compileSetters(array $setters, $var)
    {
        // Make injection point
        $ip = new InjectionPoint();
        $ip->class = $this->definition->getClass();

        // Compile setters
        $stmts = array();
        foreach( $setters as $method => $param ) {
            $ip->method = $method;
            $stmts[] = new Node\Expr\MethodCall(clone $var, $method, array(
                new Node\Arg($this->compileParam($param, $ip))
            ));
        }

        return $stmts;
    }

    protected function compileInterfaces($var)
    {
        $stmts = array();
        $class = $this->definition->getClass();
        if( !class_exists($class, true) ) {
            return array();
        }
        $r = new \ReflectionClass($class);
        foreach( $r->getInterfaceNames() as $name ) {
            if( !isset($this->definitions[$name]) ) {
                continue;
            }
            $definition = $this->definitions[$name];
            if( !($definition instanceof Definition\InterfaceDefinition) ) {
                // Ignore aliased interfaces for interface injection
                continue;
                // throw new Exception\DomainException('Interface definition not instance of InterfaceDefinition');
            }
            $setters = $definition->getSetters();
            $stmts = array_merge(
                $stmts,
                $this->compileSetters($setters, $var)
            );
        }
        return $stmts;
    }
}
