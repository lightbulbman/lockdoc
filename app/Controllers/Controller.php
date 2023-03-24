<?php

namespace Lockdoc\Controllers;

use DI\Container;

Class Controller 
{

    protected $container;
    
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

}
