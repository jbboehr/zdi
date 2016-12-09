<?php

namespace zdi\Compiler\DefinitionCompiler;

use PhpParser\Node;

use zdi\Container;
use zdi\Compiler\DefinitionCompiler;
use zdi\Definition;
use zdi\Definition\DataDefinition;
use zdi\Exception;
use zdi\InjectionPoint;
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
            // Add instance check
            $method->addStmt($this->makeSingletonCheck());
            $prop = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $identifier);
        }

        // Prepare return variable
        if( $definition->isFactory() ) {
            $retVar = new Node\Expr\Variable($identifier);
        } else {
            $retVar = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $identifier);
        }

        // Make injection point
        $ip = new InjectionPoint();
        $ip->class = $definition->getClass();
        $ip->method = '__construct';

        // Compile constructor
        $paramNodes = array();
        foreach( $definition->getParams() as $position => $param ) {
            $paramNodes[] = $this->compileParam($param, $ip);
        }
        $construct = new Node\Expr\New_(new Node\Name\FullyQualified($definition->getClass()), $paramNodes);

        // Compile setters
        $setters = $this->compileSetters($definition->getSetters(), $retVar);

        // Compile interface injection
        //$setters = array_merge($setters, $this->compileInterfaces($retVar));

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
        return $method;
    }
}
