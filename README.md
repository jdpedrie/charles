# Charles: Run PHPDoc code examples in PHPUnit

Charles is just a guy who wants to use your library. He likes copying and
pasting example code to see it in action. In fact, he wants to copy and paste
all your examples to help you make sure it works!

## Disclaimer

This library is in its very early stages and shouldn't be used for anything
important.

## Who is Charles for?

This library was developed to scratch an itch. The majority of documentation in
our library is inline in phpdoc comments. Keeping this information up-to-date
proved to be a challenge. Charles was developed to make it easier to know when
inline example code fell out of date or was simply incorrect.

## Usage

If you know how PHPUnit works, you're 80% there!

Your Test Cases should extend `Jdp\Charles\TestCase` instead
of `PHPUnit_Framework_TestCase`.

### Example

````php
<?php

namespace My\Project;

class MyClass
{
    private $foo;

    /**
     * Create an instance of MyClass.
     *
     * Example:
     * ```
     * $myClass = new \My\Project\MyClass('bar');
     * ```
     *
     * ```
     * // This will cause an error.
     * $myClass = new \My\Project\MyClass(['foo' => 'bar']);
     * ```
     *
     * @param string $foo A foo
     */
    public function __construct($foo)
    {
        if (!is_string($foo)) {
            throw new \InvalidArgumentException('foo must be a string');
        }

        $this->foo = $foo;
    }

    /**
     * Get the value of foo
     *
     * Example:
     * ```
     * $foo = $myClass->getFoo();
     * ```
     *
     * @return string
     */
    public function getFoo()
    {
        return $this->foo;
    }
}
````

And a testcase:

````php
<?php

use My\Project\MyClass;
use Jdp\Charles\TestCase;

/**
 * @integration My\Project\MyClass
 */
class MyClassTest extends TestCase
{
    /**
     * @integration __construct
     * @exampleIndex 0
     * @returnVariable myClass
     */
    public function testThatConstructorConstructs()
    {
        // `runExample` will run the 0-index example on
        // `My\Project\MyClass::__construct()` and return an instance of
        // `Jdp\Charles\Result`.
        $res = $this->runExample();

        $this->assertInstanceOf(MyClass::class, $res->getResult());
    }

    /**
     * @integration __construct
     * @exampleIndex 1
     * @expectedException InvalidArgumentException
     */
    public function testThatConstructorThrowsInvalidArgumentException()
    {
        $this->runExample();
    }

    /**
     * @integration getFoo
     * @returnVariable foo
     * @dependsOn __construct
     */
    public function testThatGetFooReturnsFoo()
    {
        // When `@dependsOn` is used, the 0-indexed example from the referenced
        // method will be prepended to the example for the targeted method.
        $res = $this->runExample();

        $this->assertEquals($res->getResult(), 'bar');
    }
}
````

### Annotations

| Annotation        | Description                                                                                                                                                                                |
|-------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `@integration`    | When used on a class, `@integration` indicates the class to use for testing. When used on a method, `@integration` indicates the method to use for testing. |
| `@exampleIndex`   | When more than one example exists in a docblock, `@exampleIndex` allows you to target a specific one by its index.                                                                         |
| `@returnVariable` | When specified, a `return $<value>;` statement will be appended to the executed example code.                                                                                              |
| `@dependsOn`      | When specified, must reference an existing method on the tested class. The 0-indexed example will be prepended to the tested example code.                                                 |

## License

Copyright 2016, John Pedrie. Licensed under the Apache 2.0 License.
