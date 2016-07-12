<?php

namespace zdi\Compiler\DefinitionCompiler;

use PhpParser\Node;

use zdi\Container;
use zdi\Compiler\DefinitionCompiler;
use zdi\Definition;
use zdi\Definition\DataDefinition;
use zdi\Exception;
use zdi\Param;
use zdi\Tests\Fixture\ContainerArgument;
use zdi\Utils;

class DataDefinitionCompiler extends AbstractDefinitionCompiler
{
    /**
     * @var DataDefinition
     */
    protected $definition;

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
            $check = $this->makeSingletonFetch();
            //$prop = $check->expr;
            $method->addStmts($check->stmts);
        }

        // Prepare return variable
        if( $definition->isFactory() ) {
            $retVar = new Node\Expr\Variable($identifier);
        } else {
            $retVar = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $identifier);
        }

        // Compile constructor
        $construct = $this->compileConstructor();
        $method->addStmts($construct->stmts);

        // Compile setters
        $setters = $this->compileSetters($definition->getSetters(), $retVar);

        // Add return statement
        if( $setters ) {
            $method->addStmt(new Node\Expr\Assign(clone $retVar, $construct->expr));
            $method->addStmts($setters);
        } else {
            $retVar = new Node\Expr\Assign(clone $retVar, $construct->expr);
        }

        // Compile return
        $method->addStmt(new Node\Stmt\Return_($retVar));

        // Return statements
        return $property ? array($property, $method) : array($method);
    }

    /**
     * @return Fetch
     */
    private function compileConstructor()
    {
        $ret = new Fetch();
        $paramNodes = array();
        foreach( $this->definition->getParams() as $position => $param ) {
            $fetch = $this->compileParam($param);
            foreach( $fetch->stmts as $stmt ) {
                $ret->stmts[] = $stmt;
            }
            $paramNodes[] = $fetch->expr;
        }
        $ret->expr = new Node\Expr\New_(new Node\Name\FullyQualified($this->definition->getClass()), $paramNodes);
        return $ret;
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
            $fetch = $this->compileParam($param);
            foreach( $fetch->stmts as $stmt ) {
                $stmts[] = $stmt;
            }
            $stmts[] = new Node\Expr\MethodCall(clone $var, $method, array(
                new Node\Arg($fetch->expr)
            ));
        }
        return $stmts;
    }

    /**
     * @param Param $param
     * @return Fetch
     * @throws Exception\DomainException
     */
    private function compileParam(Param $param)
    {
        $ret = new Fetch();
        if( $param instanceof Param\ClassParam ) {
            $key = $param->getClass();
            // Just return this if it's asking for a container
            if( $key == Container::class ) {
                $ret->expr = new Node\Expr\Variable('this');
            } else {
                $ret = $this->resolveFetch($key, $param->isOptional());
            }
        } else if( $param instanceof Param\NamedParam ) {
            $definition = $this->resolveAlias($param->getName(), true);
            if( $definition ) {
                $ret->expr = new Node\Expr\MethodCall(new Node\Expr\Variable('this'), $definition->getIdentifier());
            } else {
                $ret->expr = new Node\Expr\MethodCall(new Node\Expr\Variable('this'), 'get', array(
                    new Node\Arg(new Node\Scalar\String_($param->getName()))
                ));
            }
        } else if( $param instanceof Param\ValueParam ) {
            $ret->expr = new Node\Arg($this->compileValue($param->getValue()));
        } else {
            throw new Exception\DomainException('Unsupported parameter: ' . Utils::varInfo($param));
        }

        return $ret;
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
