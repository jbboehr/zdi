<?php

namespace zdi\Compiler\DefinitionCompiler;

use PhpParser\BuilderFactory;

use zdi\Compiler\DefinitionCompiler;
use zdi\Definition;
use zdi\Exception;

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
     * @param BuilderFactory $builderFactory
     * @param Definition $definition
     * @param Definition[] $definitions
     */
    final public function __construct(BuilderFactory $builderFactory, Definition $definition, array $definitions)
    {
        $this->builderFactory = $builderFactory;
        $this->definition = $definition;
        $this->definitions = $definitions;
    }

    /**
     * @param string $key
     * @param integer $depth
     * @return Definition|null
     */
    protected function resolveAlias($key, $isOptional = false, $depth = 0)
    {
        if( !isset($this->definitions[$key]) ) {
            if( $isOptional ) {
                return null;
            }
            throw new Exception\DomainException('Undefined key: ' . $key);
        } else if( $depth > 10 ) {
            throw new Exception\DomainException('Recursive alias detected');
        }
        $definition = $this->definitions[$key];
        if( ($definition instanceof Definition\AliasDefinition) ) {
            return $this->resolveAlias($definition->getKey());
        } else {
            return $definition;
        }
    }
}