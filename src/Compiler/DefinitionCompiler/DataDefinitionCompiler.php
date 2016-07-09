<?php

namespace zdi\Compiler\DefinitionCompiler;

use PhpParser\BuilderFactory;
use PhpParser\Node;

use zdi\Container;
use zdi\Compiler\DefinitionCompiler;
use zdi\Definition;
use zdi\Definition\DataDefinition;
use zdi\Exception;
use zdi\Param;
use zdi\Utils;

class DataDefinitionCompiler implements DefinitionCompiler
{
    /**
     * @var BuilderFactory
     */
    private $builderFactory;

    /**
     * @var Definition
     */
    private $definition;

    /**
     * @param BuilderFactory $builderFactory
     * @param DataDefinition $definition
     */
    public function __construct(BuilderFactory $builderFactory, DataDefinition $definition)
    {
        $this->builderFactory = $builderFactory;
        $this->definition = $definition;
    }

    /**
     * @inheritdoc
     */
    public function compile()
    {
        $definition = $this->definition;
        $identifier = $definition->getIdentifier();

        // Prepare method
        $method = $this->builderFactory->method($identifier)
            ->makeProtected()
            ->setDocComment('/**
                              * @return ' . $definition->getTypeHint() . '
                              */');

        // Prepare instance check
        $property = null;
        if( !$definition->isFactory() ) {
            // Add property to store instance
            $property = $this->builderFactory->property($identifier)
                ->makePrivate()
                ->setDocComment('/**
                               * @var ' . $definition->getTypeHint() . '
                               */');

            // Add instance check
            $prop = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $identifier);
            $method->addStmt(new Node\Stmt\If_(
                new Node\Expr\Isset_(array($prop)),
                array('stmts' => array(new Node\Stmt\Return_($prop)))
            ));
        }

        // Prepare return variable
        if( $definition->isFactory() ) {
            $retVar = new Node\Expr\Variable($identifier);
        } else {
            $retVar = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $identifier);
        }

        // Compile constructor
        $paramNodes = array();
        foreach( $definition->getParams() as $position => $param ) {
            $paramNodes[] = $this->compileParam($param);
        }
        $construct = new Node\Expr\New_(new Node\Name\FullyQualified($definition->getClass()), $paramNodes);

        // Compile setters
        $setters = $this->compileSetters($definition->getSetters(), $retVar);

        // Add return statement
        if( $setters ) {
            $method->addStmt(new Node\Expr\Assign(clone $retVar, $construct));
            $method->addStmts($setters);
        } else {
            $retVar = new Node\Expr\Assign(clone $retVar, $construct);
        }

        // Compile return
        $method->addStmt(new Node\Stmt\Return_($retVar));

        // Return statements
        return $property ? array($property, $method) : array($method);
    }

    /**
     * @param Param[] $setters
     * @param Node\Expr $var
     * @return Node\Stmt[]
     */
    private function compileSetters(array $setters, $var)
    {
        $stmts = array();
        foreach( $setters as $method => $param ) {
            $stmts[] = new Node\Expr\MethodCall(clone $var, $method, array(
                new Node\Arg($this->compileParam($param))
            ));
        }
        return $stmts;
    }

    /**
     * @param Param $param
     * @return Node\Expr
     * @throws Exception\DomainException
     */
    private function compileParam(Param $param)
    {
        if( $param instanceof Param\ClassParam ) {
            $identifier = $param->getIdentifier();
            // Just return this if it's asking for a container
            if( $identifier === Utils::classToIdentifier(Container::class) ) {
                return new Node\Expr\Variable('this');
            }
            $fetch = new Node\Expr\MethodCall(new Node\Expr\Variable('this'), $identifier);
            if( $param->isOptional() ) {
                // @todo resolve this at compile-time
                return new Node\Expr\Ternary(
                    new Node\Expr\FuncCall(new Node\Name('method_exists'), array(
                        new Node\Arg(new Node\Expr\Variable('this')),
                        new Node\Arg(new Node\Scalar\String_($identifier))
                    )),
                    $fetch,
                    new Node\Expr\ConstFetch(new Node\Name('null'))
                );
            }
            return $fetch;
        } else if( $param instanceof Param\NamedParam ) {
            return new Node\Expr\MethodCall(new Node\Expr\Variable('this'), 'get', array(
                new Node\Arg(new Node\Scalar\String_($param->getName()))
            ));
        } else if( $param instanceof Param\ValueParam ) {
            return new Node\Arg($this->compileValue($param->getValue()));
        } else {
            throw new Exception\DomainException('Unsupported parameter: ' . Utils::varInfo($param));
        }
    }

    /**
     * @param mixed $value
     * @return Node\Expr
     * @throws Exception\DomainException
     */
    private function compileValue($value)
    {
        return Utils::parserNodeFromValue($value);
    }
}
