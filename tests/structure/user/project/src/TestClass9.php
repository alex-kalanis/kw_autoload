<?php

namespace user\project;

// must be direct - problems on selecting correct file with namespaced content inside the php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'out.php';


use function user\project\out\TestFunc1;
use const user\project\out\TEST_CONST_1;


// paths to external sources
class TestClass9
{
    public function __construct()
    {
        TestFunc1(TEST_CONST_1);
    }
}
