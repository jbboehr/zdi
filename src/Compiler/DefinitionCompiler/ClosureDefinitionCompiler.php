<?php

namespace zdi\Compiler\DefinitionCompiler;

use ReflectionFunction;

use PhpParser\BuilderFactory;
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

class ClosureDefinitionCompiler implements DefinitionCompiler
{
    /**
     * @var BuilderFactory
     */
    private $builderFactory;

    /**
     * @var ClosureDefinition
     */
    private $definition;

    /**
     * @param BuilderFactory $builderFactory
     * @param ClosureDefinition $definition
     */
    public function __construct(BuilderFactory $builderFactory, ClosureDefinition $definition)
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

        // Translate closure
        $ast = $this->locateClosure(new ReflectionFunction($definition->getClosure()));
        $paramName = $this->getContainerParamName($ast);
        $stmts = $ast->getStmts();
        $stmts = $this->translateContainer($stmts, $paramName);
        $stmts = $this->translateReturnStatements($stmts);
        $method->addStmts($stmts);

        return $property ? array($property, $method) : array($method);
    }

    /**
     * @param Node\Expr\Closure $ast
     * @return string|null
     * @throws Exception\DomainException
     */
    private function getContainerParamName(Node\Expr\Closure $ast)
    {
        $params = $ast->getParams();
        if( count($params) <= 0 ) {
            return null;
        } else if( count($params) > 1 ) {
            // @codeCoverageIgnoreStart
            // Note: This should be handled by the container builder
            throw new Exception\DomainException("Closure must have only one or zero parameters");
            // @codeCoverageIgnoreEnd
        }
        $param = $params[0];
        $type = $param->type;

        if( !$type ) {
            // @codeCoverageIgnoreStart
            // Note: This should be handled by the container builder
            throw new Exception\DomainException('Closure provider parameter must have a typehint');
            // @codeCoverageIgnoreEnd
        } else {
            if( !($type instanceof Node\Name\FullyQualified) ) {
                // @codeCoverageIgnoreStart
                // Note: Not sure if reachable
                throw new Exception\DomainException("Type must be a fully quality class name");
                // @codeCoverageIgnoreEnd
            }
            $paramClass = $type->toString();
            $interfaceClass = Container::class;
            if( $paramClass !== $interfaceClass && !is_subclass_of('\\' . $paramClass, $interfaceClass) ) {
                // @codeCoverageIgnoreStart
                // Note: This should be handled by the container builder
                throw new Exception\DomainException("Closure parameter must be zdi\\Container or a subclass");
                // @codeCoverageIgnoreEnd
            }
        }
        return $param->name;
    }

    /**
     * @param Node[] $stmts
     * @param string $paramName
     * @return Node[]
     */
    private function translateContainer(array $stmts, $paramName)
    {
        $visitor = new Visitor\ContainerTranslatorVisitor($paramName);
        $fileTraverser = new NodeTraverser;
        $fileTraverser->addVisitor($visitor);
        return $fileTraverser->traverse($stmts);
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
