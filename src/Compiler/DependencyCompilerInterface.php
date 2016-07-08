<?php

namespace zdi\Compiler;

use PhpParser\BuilderAbstract;

interface DependencyCompilerInterface
{
    /**
     * @return BuilderAbstract[]
     */
    public function compile();
}
