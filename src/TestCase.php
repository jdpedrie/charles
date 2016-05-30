<?php

namespace Jdp\Charles;

use Jdp\Charles\TestCase\TestClass;
use Jdp\Charles\TestCase\TestMethod;
use ReflectionMethod;
use phpDocumentor\Reflection\DocBlock;

class TestCase extends \PHPUnit_Framework_TestCase
{
    const EXAMPLE_REGEX = '/[.??^`]{0,}```(.*?)```[.??^`]{0,}/s';

    /**
     * @var callable
     */
    private $exampleCode;

    /**
     * Run the example code and get a result
     *
     * @param  array $locals A key/value list of variables to be passed to the
     *         example code. These variables will be extracted into the local
     *         scope of the example run.
     * @return Result
     * @throws Jdp\Charles\Exception
     */
    public function runExample($locals = [])
    {
        if (is_null($this->exampleCode)) {
            throw new Exception('Could not run integration tests because no code could be parsed');
        }

        $fn = $this->exampleCode;
        return $fn($locals);
    }

    protected function checkRequirements()
    {
        parent::checkRequirements();

        $annotations = $this->getAnnotations();
        $class = new TestClass($annotations);
        $method = new TestMethod($annotations);

        try {
            $this->checkValidity($class, $method);
        } catch (Exception $e) {
            $this->exampleCode = null;

            throw $e;
        }

        $code = $this->getExamplesFromMethod($class->integration(), $method->integration());
        if (count($code) > 1 && is_null($method->exampleIndex())) {
            $this->exampleCode = null;
            throw new Exception(sprintf(
                'Ambiguous reference to code example block. In docblocks with ' .
                'multiple examples, please use @exampleIndex to target the correct example.' .
                PHP_EOL .
                PHP_EOL .
                'Error occurred on test of %s::%s',
                $class,
                $method
            ));
        }

        $index = $method->exampleIndex() ? $method->exampleIndex() : 0;
        $code = $code[(int)$index];

        foreach ($method->dependsOn() as $depend) {
            $dependency = $this->getExamplesFromMethod($class->integration(), $depend);
            $code = $dependency[0] . $code;
        }

        if (!is_null($method->returnVariable())) {
            $returnVariableCleaned = $method->returnVariable();
            if (strpos($method->returnVariable(), '$') === false) {
                $returnVariableCleaned = '$'. $method->returnVariable();
            }

            $code = $code . 'return '. $returnVariableCleaned .';';
        }

        $eval = function(array $locals = []) use ($code) {
            extract($locals);

            ob_start();
            $res = eval($code);
            $output = ob_end_clean();

            return new Result($res, $output);
        };

        $this->exampleCode = $eval;
    }

    private function checkValidity(TestClass $class, TestMethod $method)
    {
        if (!class_exists($class->integration())) {
            throw new Exception(sprintf(
                'Integration Class %s does not exist',
                $class
            ));
        }

        if (!method_exists($class->integration(), $method->integration())) {
            throw new Exception(sprintf(
                'Integration Method %s does not exist on class %s',
                $method,
                $class
            ));
        }
    }

    private function getExamplesFromMethod($class, $method)
    {
        $class = new ReflectionMethod($class, $method);
        $doc = new DocBlock($class);
        $text = $doc->getText();

        $examples = [];

        preg_match_all(self::EXAMPLE_REGEX, $text, $examples);

        return $examples[1];
    }
}
