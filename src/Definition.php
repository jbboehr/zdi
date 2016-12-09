<?php

namespace zdi;

interface Definition
{
    const FACTORY = 1;
    const IS_GLOBAL = 2;

    public function getClass();

    public function getName();

    public function getKey() : string;

    public function getIdentifier() : string;

    public function getTypeHint() : string;

    public function isFactory() : bool;

    public function isGlobal() : bool;
}
