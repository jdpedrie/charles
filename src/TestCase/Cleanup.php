<?php

namespace Jdp\Charles\TestCase;

use Jdp\Charles\Exception;

class Cleanup
{
    private $jobs = [];

    public function __construct(array $annotations = [])
    {
        if (isset($annotations['cleanup'])) {
            $this->requirements = $annotations['cleanup'];
        }
    }

    public function addJob($job, callable $callback)
    {
        $this->jobs[] = [
            'job' => $job,
            'callback' => $callback
        ];
    }

    public function cleanup()
    {
        foreach ($this->jobs as $job) {
            $fn = $job['callback'];
            $fn();
        }
    }
}
