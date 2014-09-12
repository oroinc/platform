<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\ColumnOptionsGuesserChain;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;

class ColumnOptionsGuesserChainTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWithInvalidGuesser()
    {
        $this->setExpectedException(
            '\Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException',
            'Expected argument of type "Oro\Bundle\DataGridBundle\Datagrid\ColumnOptionsGuesserInterface"'
            . ', "stdClass" given'
        );
        new ColumnOptionsGuesserChain([new \stdClass()]);
    }

    public function testConstructorWithInvalidGuesserScalar()
    {
        $this->setExpectedException(
            '\Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException',
            'Expected argument of type "Oro\Bundle\DataGridBundle\Datagrid\ColumnOptionsGuesserInterface"'
            . ', "string" given'
        );
        new ColumnOptionsGuesserChain(['test']);
    }

    public function testConstructorWithInvalidGuesserNull()
    {
        $this->setExpectedException(
            '\Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException',
            'Expected argument of type "Oro\Bundle\DataGridBundle\Datagrid\ColumnOptionsGuesserInterface"'
            . ', "NULL" given'
        );
        new ColumnOptionsGuesserChain([null]);
    }

    public function testConstructorWithChainGuessers()
    {
        $guesser1 = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ColumnOptionsGuesserInterface');
        $guesser2 = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ColumnOptionsGuesserInterface');
        $guesser3 = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ColumnOptionsGuesserInterface');

        $chainGuesser = new ColumnOptionsGuesserChain(
            [
                $guesser1,
                new ColumnOptionsGuesserChain([$guesser2, $guesser3])
            ]
        );

        $this->assertAttributeSame(
            [$guesser1, $guesser2, $guesser3],
            'guessers',
            $chainGuesser
        );
    }

    public function testGuessFormatter()
    {
        $this->doTestGuess('guessFormatter');
    }

    public function testGuessSorter()
    {
        $this->doTestGuess('guessSorter');
    }

    public function testGuessFilter()
    {
        $this->doTestGuess('guessFilter');
    }

    public function doTestGuess($guessMethodName)
    {
        $class    = 'TestClass';
        $property = 'testProp';
        $type     = 'integer';

        $guess1 = new ColumnGuess([], ColumnGuess::LOW_CONFIDENCE);
        $guess2 = new ColumnGuess([], ColumnGuess::HIGH_CONFIDENCE);

        $guesser1 = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ColumnOptionsGuesserInterface');
        $guesser2 = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ColumnOptionsGuesserInterface');
        $guesser3 = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ColumnOptionsGuesserInterface');

        $guesser1->expects($this->once())
            ->method($guessMethodName)
            ->with($class, $property, $type)
            ->will($this->returnValue($guess1));
        $guesser2->expects($this->once())
            ->method($guessMethodName)
            ->with($class, $property, $type)
            ->will($this->returnValue(null));
        $guesser3->expects($this->once())
            ->method($guessMethodName)
            ->with($class, $property, $type)
            ->will($this->returnValue($guess2));

        $chainGuesser = new ColumnOptionsGuesserChain([$guesser1, $guesser2, $guesser3]);

        $guess = $chainGuesser->$guessMethodName($class, $property, $type);
        $this->assertSame($guess2, $guess);
    }
}
