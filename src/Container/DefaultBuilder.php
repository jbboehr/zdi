<?php

namespace zdi\Container;

use zdi\Container;

class DefaultBuilder extends Builder
{
    /**
     * @inheritdoc
     */
    public function build()
    {
        return new Container\RuntimeContainer(array(), $this->getDefinitions());
    }

    public function isReady()
    {
        return false;
    }
}
