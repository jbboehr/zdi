<?php

namespace zdi;

use zdi\Container\ContainerBuilder;

interface Module
{
    public function define(ContainerBuilder $builder);
}
