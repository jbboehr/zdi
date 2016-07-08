<?php

namespace zdi\Compiler;

use PhpParser\Builder;

interface DependencyCompilerInterface
{
    /**
     * @return Builder\Method
     */
    public function compile();
}
