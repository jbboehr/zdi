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
        return new Container(array(), $this->getDependencies());
    }

    public function isReady()
    {
        return false;
    }
}
