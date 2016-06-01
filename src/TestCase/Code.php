<?php

namespace Jdp\Charles\TestCase;

use Jdp\Charles\Exception;

class Code
{
    private $code;
    private $prepends = [];
    private $appends = [];
    private $return;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function prepend($code)
    {
        $this->prepends[] = $code;
    }

    public function append($code)
    {
        $this->appends[] = $code;
    }

    public function appendReturnStatement($varName)
    {
        $returnVariable = str_replace('$$', '$', '$'. $varName);

        $this->append('return '. $returnVariable .';');
    }

    public function execute(array $locals = [])
    {
        extract($locals);

        try {
            ob_start();
            $res = eval($this->build());
            $output = ob_get_clean();
        } catch (\Exception $err) {
            throw new Exception(sprintf(
                'Error message: %s' .
                PHP_EOL .
                PHP_EOL .
                '%s',

                $err->getMessage(), $this->code
            ), $err->getCode(), $err);
        }

        return new Result($res, $output);
    }

    private function build()
    {
        $prepends = implode(PHP_EOL.PHP_EOL, $this->prepends);
        $appends = implode(PHP_EOL.PHP_EOL, $this->appends);

        return implode(PHP_EOL.PHP_EOL, [$prepends, $this->code, $appends, $this->return]);
    }
}
