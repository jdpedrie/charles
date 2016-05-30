<?php

namespace Jdp\Charles\TestCase;

use LogicException;

class TestClass
{
    private $integration;

    public function __construct(array $annotations)
    {
        if (!isset($annotations['class']['integration'][0])) {
            throw new LogicException('integration class is not specified');
        }

        $this->integration = $annotations['class']['integration'][0];
    }

    public function integration()
    {
        return $this->integration;
    }
}
