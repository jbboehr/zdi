<?php

namespace zdi\Compiler\DefinitionCompiler;

use ReflectionFunction;

use PhpParser\NodeTraverser;
use PhpParser\Node;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Error as ParserError;
use PhpParser\ParserFactory;

use SuperClosure\Analyzer\Visitor\ClosureLocatorVisitor;
use SuperClosure\Exception\ClosureAnalysisException;

use zdi\Container;
use zdi\Compiler\Visitor;
use zdi\Compiler\DefinitionCompiler;
use zdi\Definition;
use zdi\Definition\ClosureDefinition;
use zdi\Exception;
use zdi\Utils;

class ClosureDefinitionCompiler extends AbstractDefinitionCompiler
{
    /**
     * @var ClosureDefinition
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
                ->makeProtected()
                ->setDocComment('/**
                               * @var ' . $definition->getTypeHint() . '
                               */');

            // Add instance check
            $check = $this->makeSingletonFetch();
            $prop = $check->expr;
//            $method->addStmts($check->stmts);
        }

        $reflectionFunction = new ReflectionFunction($definition->getClosure());

        // Translate closure
        $ast = $this->locateClosure($reflectionFunction);
        $stmts = $ast->getStmts();
        $stmts = $this->translateParameters($reflectionFunction, $stmts);
        $stmts = $this->translateReturnStatements($stmts);
        $method->addStmts($stmts);

        return $property ? array($property, $method) : array($method);
    }

    private function translateParameters(ReflectionFunction $reflectionFunction, $stmts)
    {
        $map = array();
        $prepend = array();
        foreach( $reflectionFunction->getParameters() as $parameter ) {
            $class = $parameter->getClass();
            if( !$class ) {
                // @codeCoverageIgnoreStart
                // Note: this should be covered by the definition builder
                throw new Exception\DomainException('Closure parameter must have a type hint');
                // @codeCoverageIgnoreEnd
            }
            $className = $parameter->getClass()->getName();
            if( $className === Container::class ) {
                $map[$parameter->getName()] = new Node\Expr\Variable('this');
            } else {
                $paramDefinition = $this->resolveAlias($className);
                $prepend[] = new Node\Expr\Assign(
                    new Node\Expr\Variable($parameter->getName()),
                    new Node\Expr\MethodCall(new Node\Expr\Variable('this'), $paramDefinition->getIdentifier())
                );
            }
        }

        $visitor = new Visitor\VariableTranslatorVisitor($map);
        $fileTraverser = new NodeTraverser;
        $fileTraverser->addVisitor($visitor);
        $stmts = $fileTraverser->traverse($stmts);

        // Prepaend
        return array_merge($prepend, $stmts);
    }

    /**
     * @param Node[] $stmts
     * @return Node[]
     */
    private function translateReturnStatements(array $stmts)
    {
        // Ignore factories
        if( $this->definition->isFactory() ) {
            return $stmts;
        }

        $visitor = new Visitor\ReturnTranslatorVisitor($this->definition->getIdentifier());
        $fileTraverser = new NodeTraverser;
        $fileTraverser->addVisitor($visitor);
        return $fileTraverser->traverse($stmts);
    }

    /**
     * @param ReflectionFunction $reflectionFunction
     * @return Node\Expr\Closure
     */
    private function locateClosure(ReflectionFunction $reflectionFunction)
    {
        try {
            $locator = new ClosureLocatorVisitor($reflectionFunction);
            $fileAst = $this->getFileAst($reflectionFunction);
            $fileTraverser = new NodeTraverser;
            $fileTraverser->addVisitor(new NameResolver);
            $fileTraverser->addVisitor($locator);
            $fileTraverser->traverse($fileAst);
        } catch (ParserError $e) { // @codeCoverageIgnoreStart
            throw new ClosureAnalysisException(
                'There was an error analyzing the closure code.', 0, $e
            );
        } // @codeCoverageIgnoreEnd
        if( !$locator->closureNode ) {
            // @codeCoverageIgnoreStart
            throw new ClosureAnalysisException(
                'The closure was not found within the abstract syntax tree.'
            );
            // @codeCoverageIgnoreEnd
        }
        return $locator->closureNode;
    }

    /**
     * @param ReflectionFunction $reflection
     * @return null|\PhpParser\Node[]
     */
    private function getFileAst(ReflectionFunction $reflection)
    {
        $fileName = $reflection->getFileName();
        if (!file_exists($fileName)) {
            // @codeCoverageIgnoreStart
            throw new ClosureAnalysisException(
                "The file containing the closure, \"{$fileName}\" did not exist."
            );
            // @codeCoverageIgnoreEnd
        }
        return $this->getParser()->parse(file_get_contents($fileName));
    }

    /**
     * @return \PhpParser\Parser
     */
    private function getParser()
    {
        return (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }
}
