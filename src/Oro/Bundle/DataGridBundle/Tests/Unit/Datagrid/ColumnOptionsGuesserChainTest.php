<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\ColumnOptionsGuesserChain;
use Oro\Bundle\DataGridBundle\Datagrid\ColumnOptionsGuesserInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;
use Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class ColumnOptionsGuesserChainTest extends TestCase
{
    public function testConstructorWithInvalidGuesser(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected argument of type "%s", "stdClass" given',
            ColumnOptionsGuesserInterface::class
        ));
        new ColumnOptionsGuesserChain([new \stdClass()]);
    }

    public function testConstructorWithInvalidGuesserScalar(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected argument of type "%s", "string" given',
            ColumnOptionsGuesserInterface::class
        ));
        new ColumnOptionsGuesserChain(['test']);
    }

    public function testConstructorWithInvalidGuesserNull(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected argument of type "%s", "NULL" given',
            ColumnOptionsGuesserInterface::class
        ));
        new ColumnOptionsGuesserChain([null]);
    }

    public function testConstructorWithChainGuessers(): void
    {
        $guesser1 = $this->createMock(ColumnOptionsGuesserInterface::class);
        $guesser2 = $this->createMock(ColumnOptionsGuesserInterface::class);
        $guesser3 = $this->createMock(ColumnOptionsGuesserInterface::class);

        $chainGuesser = new ColumnOptionsGuesserChain([
            $guesser1,
            new ColumnOptionsGuesserChain([$guesser2, $guesser3])
        ]);

        self::assertSame(
            [$guesser1, $guesser2, $guesser3],
            ReflectionUtil::getPropertyValue($chainGuesser, 'guessers')
        );
    }

    public function testGuessFormatter(): void
    {
        $this->doTestGuess('guessFormatter');
    }

    public function testGuessSorter(): void
    {
        $this->doTestGuess('guessSorter');
    }

    public function testGuessFilter(): void
    {
        $this->doTestGuess('guessFilter');
    }

    public function doTestGuess($guessMethodName)
    {
        $class = 'TestClass';
        $property = 'testProp';
        $type = 'integer';

        $guess1 = new ColumnGuess([], ColumnGuess::LOW_CONFIDENCE);
        $guess2 = new ColumnGuess([], ColumnGuess::HIGH_CONFIDENCE);

        $guesser1 = $this->createMock(ColumnOptionsGuesserInterface::class);
        $guesser2 = $this->createMock(ColumnOptionsGuesserInterface::class);
        $guesser3 = $this->createMock(ColumnOptionsGuesserInterface::class);

        $guesser1->expects($this->once())
            ->method($guessMethodName)
            ->with($class, $property, $type)
            ->willReturn($guess1);
        $guesser2->expects($this->once())
            ->method($guessMethodName)
            ->with($class, $property, $type)
            ->willReturn(null);
        $guesser3->expects($this->once())
            ->method($guessMethodName)
            ->with($class, $property, $type)
            ->willReturn($guess2);

        $chainGuesser = new ColumnOptionsGuesserChain([$guesser1, $guesser2, $guesser3]);

        $guess = $chainGuesser->$guessMethodName($class, $property, $type);
        $this->assertSame($guess2, $guess);
    }
}
