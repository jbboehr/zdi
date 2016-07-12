<?php

namespace zdi\Compiler\DefinitionCompiler;

use PhpParser\Node;

class Fetch
{
    /**
     * @var Node[]
     */
    public $stmts = array();

    /**
     * @var Node\Expr
     */
    public $expr;

    /**
     * @var boolean
     */
    public $found = true;
}
