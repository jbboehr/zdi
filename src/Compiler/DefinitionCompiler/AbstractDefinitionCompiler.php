<?php

namespace zdi\Compiler\DefinitionCompiler;

use PhpParser\BuilderFactory;
use PhpParser\Node;

use zdi\Compiler\DefinitionCompiler;
use zdi\Container;
use zdi\Definition;
use zdi\Exception;
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
     * @return Node\Expr
     * @throws Exception\DomainException
     */
    protected function compileParam(Param $param)
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
                return new Node\Expr\MethodCall(new Node\Expr\Variable('this'), $definition->getIdentifier());
            } else {
                return new Node\Expr\ConstFetch(new Node\Name('null'));
            }
        } else if( $param instanceof Param\NamedParam ) {
            $definition = Utils::resolveAliasKey($this->definitions, $param->getName(), true);
            if( $definition ) {
                return new Node\Expr\MethodCall(new Node\Expr\Variable('this'), $definition->getIdentifier());
            } else {
                return new Node\Expr\MethodCall(new Node\Expr\Variable('this'), 'get', array(
                    new Node\Arg(new Node\Scalar\String_($param->getName()))
                ));
            }
        } else if( $param instanceof Param\ValueParam ) {
            return new Node\Arg($this->compileValue($param->getValue()));
        } else {
            throw new Exception\DomainException('Unsupported parameter: ' . Utils::varInfo($param) . ' for definition: ' . $this->definition->getKey());
        }
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
}
