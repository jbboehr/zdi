<?php

namespace zdi\Compiler\DefinitionCompiler;

use PhpParser\Node;

use zdi\Container;
use zdi\Compiler\DefinitionCompiler;
use zdi\Definition;
use zdi\Definition\ClassDefinition;
use zdi\Exception;
use zdi\Utils;

class ClassDefinitionCompiler extends AbstractDefinitionCompiler
{
    /**
     * @var ClassDefinition
     */
    protected $definition;

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
        $prop = null;
        if( !$definition->isFactory() ) {
            $method->addStmt($this->makeSingletonCheck());
            $prop = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $identifier);
        }

        // Prepare method body
        $providerDefinition = $this->resolveAlias($definition->getProvider());
        $fetch = new Node\Expr\MethodCall(new Node\Expr\MethodCall(new Node\Expr\Variable('this'), $providerDefinition->getIdentifier()), 'get');

        if( !$definition->isFactory() ) {
            $method->addStmt(new Node\Stmt\Return_(new Node\Expr\Assign(clone $prop, $fetch)));
        } else {
            $method->addStmt(new Node\Stmt\Return_($fetch));
        }

        return $method;
    }
}
