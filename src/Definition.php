<?php

namespace zdi;

interface Definition
{
    public function getClass();

    public function getName();

    public function getKey();

    public function getIdentifier();

    public function getTypeHint();

    public function isFactory();
}
