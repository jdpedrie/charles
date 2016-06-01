<?php

namespace Jdp\Charles\TestCase;

use LogicException;

class TestMethod
{
    private $integration;
    private $exampleIndex;
    private $returnVariable;
    private $dependsOn;

    public function __construct(array $annotations)
    {
        if (!isset($annotations['integration'])) {
            throw new LogicException('integration method is required');
        }

        $this->integration = $annotations['integration'][0];

        $this->exampleIndex = (isset($annotations['exampleIndex']))
            ? $annotations['exampleIndex'][0]
            : null;

        $this->returnVariable = (isset($annotations['returnVariable']))
            ? $annotations['returnVariable'][0]
            : null;

        $this->dependsOn = (isset($annotations['dependsOn']))
            ? $annotations['dependsOn']
            : [];
    }

    public function integration()
    {
        return $this->integration;
    }

    public function exampleIndex()
    {
        return $this->exampleIndex;
    }

    public function returnVariable()
    {
        return $this->returnVariable;
    }

    public function dependsOn()
    {
        return $this->dependsOn;
    }
}
