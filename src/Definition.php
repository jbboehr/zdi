<?php

namespace zdi;

interface Definition
{
    const FACTORY = 1;
    const IS_GLOBAL = 2;

    public function getClass();

    public function getName();

    public function getKey();

    public function getIdentifier();

    public function getTypeHint();

    public function isFactory();

    public function isGlobal();
}
