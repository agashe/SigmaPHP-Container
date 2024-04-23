<?php

namespace SigmaPHP\Container\Tests\Examples;

class Box
{
    /**
     * @var float $height
     */
    private $height;
    
    /**
     * @var float $width
     */
    private $width;
    
    /**
     * @var float $length
     */
    private $length;

    /**
     * Box Constructor
     */
    public function __construct($height, $width, $length = 50) {
        $this->height = $height;
        $this->width = $width;
        $this->length = $length;
    }
}