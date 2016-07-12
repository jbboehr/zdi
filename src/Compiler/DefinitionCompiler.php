<?php

namespace zdi\Compiler;

use PhpParser\BuilderAbstract;

interface DefinitionCompiler
{
    /**
     * @return BuilderAbstract
     */
    public function compile();
}
