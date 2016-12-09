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
use zdi\InjectionPoint;
use zdi\Param;
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

        // Add parameter for injection point
        if( $definition->hasInjectionPointParam() ) {
            $method->addParam(
                $this->builderFactory->param('ipClass')
                    ->setDefault(null)
            );
            $method->addParam(
                $this->builderFactory->param('ipMethod')
                    ->setDefault(null)
            );
        }

        // Prepare instance check
        if( !$definition->isFactory() ) {
            $method->addStmt($this->makeSingletonCheck());
        }

        $reflectionFunction = new ReflectionFunction($definition->getClosure());

        // Translate closure
        $ast = $this->locateClosure($reflectionFunction);
        $stmts = $ast->getStmts();
        $stmts = $this->translateParameters($reflectionFunction, $stmts);
        $stmts = $this->translateReturnStatements($stmts);
        $method->addStmts($stmts);

        return $method;
    }

    /**
     * @param ReflectionFunction $reflectionFunction
     * @param Node[] $stmts
     * @return Node[]
     * @throws Exception\DomainException
     */
    private function translateParameters(ReflectionFunction $reflectionFunction, $stmts)
    {
        $map = array();
        $prepend = array();
        $reflectionParameters = $reflectionFunction->getParameters();
        foreach( $this->definition->getParams() as $position => $param ) {
            $reflectionParameter = $reflectionParameters[$position];
            if( $param instanceof Param\ClassParam && $param->getClass() === Container::class ) {
                $map[$reflectionParameter->getName()] = new Node\Expr\Variable('this');
            } else if( $param instanceof Param\InjectionPointParam ) {
                $ip = new InjectionPoint();
                $map[$reflectionParameter->getName()] = new InjectionPoint();
            } else {
                $ip = new InjectionPoint();
                $ip->class = $this->definition->getClass();
                $prepend[] = new Node\Expr\Assign(
                    new Node\Expr\Variable($reflectionParameter->getName()),
                    $this->compileParam($param, $ip)
                );
            }
        }

        $visitor = new Visitor\VariableTranslatorVisitor($map);
        $fileTraverser = new NodeTraverser;
        $fileTraverser->addVisitor($visitor);
        $stmts = $fileTraverser->traverse($stmts);

        // Prepend
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
            $var = new Node\Expr\Variable($this->definition->getIdentifier());
        } else {
            $var = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $this->definition->getIdentifier());
        }

        $setterStmts = $this->compileSetters($this->definition->getSetters(), $var);

        $visitor = new Visitor\ReturnTranslatorVisitor($var, $setterStmts);
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
        if( isset($this->astCache[$fileName]) ) {
            return $this->astCache[$fileName];
        }
        if (!file_exists($fileName)) {
            // @codeCoverageIgnoreStart
            throw new ClosureAnalysisException(
                "The file containing the closure, \"{$fileName}\" did not exist."
            );
            // @codeCoverageIgnoreEnd
        }
        return $this->astCache[$fileName] = $this->getParser()->parse(file_get_contents($fileName));
    }

    /**
     * @return \PhpParser\Parser
     */
    private function getParser()
    {
        return (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }
}
