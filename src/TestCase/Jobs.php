<?php

namespace Jdp\Charles\TestCase;

use Jdp\Charles\Exception;
use Jdp\Charles\TestCase;

class Jobs
{
    private $testCase;
    private $jobs = [];

    public function __construct(TestCase $testCase, array $jobs = [])
    {
        $this->testCase = $testCase;

        foreach ($jobs as $job) {
            $this->addJob($job);
        }
    }

    public function addJob($callback)
    {
        $this->jobs[] = [$this->testCase, $callback];
    }

    public function run()
    {
        foreach ($this->jobs as $job) {
            $job();
        }
    }
}
