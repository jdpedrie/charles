<?php

namespace Jdp\Charles;

use DOMDocument;
use Jdp\Charles\TestCase\Jobs;
use Jdp\Charles\TestCase\Code;
use Jdp\Charles\TestCase\TestClass;
use Jdp\Charles\TestCase\TestMethod;
use ReflectionMethod;
use Parsedown;
use phpDocumentor\Reflection\DocBlock;
use PHPUnit_Framework_TestCase;

class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var callable
     */
    private $exampleCode;

    /**
     * @var Jobs
     */
    private $setup;

    /**
     * @var Jobs
     */
    private $cleanup;

    /**
     * @var Code
     */
    private $code;

    /**
     * Run the example code and get a result
     *
     * @param  array $locals A key/value list of variables to be passed to the
     *         example code. These variables will be extracted into the local
     *         scope of the example run.
     * @return Jdp\Charles\Result
     * @throws Jdp\Charles\Exception
     */
    public function runExample($locals = [])
    {
        if (is_null($this->code)) {
            throw new Exception('Could not run integration tests because no code could be parsed');
        }

        return $this->code->execute($locals);
    }

    public function setUp()
    {
        parent::setUp();

        $this->setup->run();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->cleanup->run();
    }

    public function getFromMethod($method, $returnVariable = null, $index = 0, array $locals = [])
    {
        $examples = $this->getExamplesFromMethod($this->exampleClass->integration(), $method);

        if (!isset($examples[$index])) {
            throw new Exception(sprintf(
                'Could not get index %d from method %s::%s',
                $index, $this->exampleClass->integration(), $method
            ));
        }

        $code = new Code($examples[$index]);

        if (!is_null($returnVariable)) {
            $code->appendReturnStatement($returnVariable);
        }

        return $code->execute($locals);
    }

    protected function checkRequirements()
    {
        parent::checkRequirements();

        $annotations = $this->getAnnotations();
        $class = $this->exampleClass = new TestClass($annotations['class']);
        $method = $this->exampleMethod = new TestMethod($annotations['method']);

        $annotations['method'] += [
            'setup' => [],
            'cleanup' => []
        ];

        $this->setup = new Jobs($this, $annotations['method']['setup']);
        $this->cleanup = new Jobs($this, $annotations['method']['cleanup']);

        try {
            $this->checkValidity($class, $method);

            $code = $this->getExamplesFromMethod($class->integration(), $method->integration());
            if (count($code) > 1 && is_null($method->exampleIndex())) {
                throw new Exception(sprintf(
                    'Ambiguous reference to code example block. In docblocks with ' .
                    'multiple examples, please use @exampleIndex to target the correct example.' .
                    PHP_EOL .
                    PHP_EOL .
                    'Error occurred on test of %s::%s',
                    $class->integration(),
                    $method->integration()
                ));
            }
        } catch (Exception $e) {
            $this->code = null;

            throw $e;
        }

        $index = $method->exampleIndex() ? (int) $method->exampleIndex() : 0;
        $this->code = new Code($code[$index]);

        foreach ($method->dependsOn() as $depend) {
            $dependency = $this->getExamplesFromMethod($class->integration(), $depend);
            $this->code->prepend($dependency[0]);
        }

        if (!is_null($method->returnVariable())) {
            $this->code->appendReturnStatement($method->returnVariable());
        }
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
                $method->integration(),
                $class->integration()
            ));
        }
    }

    private function getExamplesFromMethod($class, $method)
    {
        $reflector = new ReflectionMethod($class, $method);
        $doc = new DocBlock($reflector);
        $text = $doc->getText();

        $parts = explode('Example:', $text);

        if (strpos($text, 'Example:') === false) {
            throw new Exception('Tested method does not have valid examples '.
                'present. Examples must be preceded by "Example:".');
        }

        $converter = new Parsedown;
        $document = new DOMDocument;

        $parsedText = $converter->text($parts[1]);
        $document->loadHTML($parsedText);

        $res = [];

        $examples = $document->getElementsByTagName('code');
        foreach ($examples as $example) {
            $res[] = $example->textContent;
        }

        return $res;
    }
}
