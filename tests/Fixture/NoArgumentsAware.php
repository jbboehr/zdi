<?php

namespace zdi\Tests\Fixture;

class NoArgumentsAware implements NoArgumentsAwareInterface
{
    public $noArguments;

    public function setNoArguments(NoArguments $noArguments)
    {
        $this->noArguments = $noArguments;
    }

    public function getNoArguments()
    {
        return $this->noArguments;
    }
}
