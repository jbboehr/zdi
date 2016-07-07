<?php

namespace zdi\Compiler;

use PhpParser\Builder;

abstract class AbstractDependencyCompiler
{
    /**
     * @return Builder\Method
     */
    abstract public function compile();
}
