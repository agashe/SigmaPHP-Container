<?php

namespace SigmaPHP\Container\Tests\Examples;

class Greeter
{
    /**
     * Print hello.
     * 
     * @return void
     */
    public function __invoke()
    {
        echo "Hello SigmaPHP-Container !" . PHP_EOL;
    }
}