<?php

namespace Jdp\Charles;

class Result
{
    private $result;
    private $output;

    public function __construct($result, $output)
    {
        $this->result = $result;
        $this->output = $output;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function getOutput()
    {
        return $this->output;
    }
}
