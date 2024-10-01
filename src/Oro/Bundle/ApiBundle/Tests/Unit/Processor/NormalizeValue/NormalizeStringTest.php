<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeString;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NormalizeStringTest extends \PHPUnit\Framework\TestCase
{
    private NormalizeString $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->processor = new NormalizeString();
    }

    public function testProcessWhenNoValueToNormalize(): void
    {
        $context = new NormalizeValueContext();

        $this->processor->process($context);

        self::assertFalse($context->hasResult());
    }

    public function testProcessForAlreadyResolvedRequirement(): void
    {
        $context = new NormalizeValueContext();
        $context->setRequirement('test');

        $this->processor->process($context);

        self::assertEquals('test', $context->getRequirement());
    }

    public function testProcess(): void
    {
        $context = new NormalizeValueContext();
        $context->setResult('test');

        $this->processor->process($context);

        self::assertEquals('.+', $context->getRequirement());
        self::assertEquals('test', $context->getResult());
    }

    public function testProcessForEmptyValue(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected not empty string value. Given "".');

        $context = new NormalizeValueContext();
        $context->setResult('');

        $this->processor->process($context);
    }

    public function testProcessForValueContainsOnlySpaces(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected not empty string value. Given "  ".');

        $context = new NormalizeValueContext();
        $context->setResult('  ');

        $this->processor->process($context);
    }

    public function testProcessForZeroValue(): void
    {
        $context = new NormalizeValueContext();
        $context->setResult('0');

        $this->processor->process($context);

        self::assertEquals('.+', $context->getRequirement());
        self::assertEquals('0', $context->getResult());
    }

    public function testProcessForEmptyValueWhenAllowEmptyOptionIsTrue(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected string value. Given "".');

        $context = new NormalizeValueContext();
        $context->setResult('');
        $context->addOption('allow_empty', true);

        $this->processor->process($context);
    }

    public function testProcessForValueContainsOnlySpacesWhenAllowEmptyOptionIsTrue(): void
    {
        $context = new NormalizeValueContext();
        $context->setResult('  ');
        $context->addOption('allow_empty', true);

        $this->processor->process($context);

        self::assertEquals('.+', $context->getRequirement());
        self::assertEquals('  ', $context->getResult());
    }

    public function testProcessForZeroValueWhenAllowEmptyOptionIsTrue(): void
    {
        $context = new NormalizeValueContext();
        $context->setResult('0');
        $context->addOption('allow_empty', true);

        $this->processor->process($context);

        self::assertEquals('.+', $context->getRequirement());
        self::assertEquals('0', $context->getResult());
    }

    public function testProcessForArray(): void
    {
        $context = new NormalizeValueContext();
        $context->setArrayAllowed(true);
        $context->setArrayDelimiter(',');
        $context->setResult('test1,test2');

        $this->processor->process($context);

        self::assertEquals('.+', $context->getRequirement());
        self::assertEquals(['test1', 'test2'], $context->getResult());
    }

    public function testProcessForArraysForEmptyValue(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected an array of not empty strings. Given ",test".');

        $context = new NormalizeValueContext();
        $context->setArrayAllowed(true);
        $context->setArrayDelimiter(',');
        $context->setResult(',test');

        $this->processor->process($context);
    }

    public function testProcessForArraysForValueContainsOnlySpaces(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected an array of not empty strings. Given "  ,test".');

        $context = new NormalizeValueContext();
        $context->setArrayAllowed(true);
        $context->setArrayDelimiter(',');
        $context->setResult('  ,test');

        $this->processor->process($context);
    }

    public function testProcessForArrayContainsZeroValue(): void
    {
        $context = new NormalizeValueContext();
        $context->setArrayAllowed(true);
        $context->setArrayDelimiter(',');
        $context->setResult('0,false');

        $this->processor->process($context);

        self::assertEquals('.+', $context->getRequirement());
        self::assertEquals(['0', 'false'], $context->getResult());
    }

    public function testProcessForArraysForEmptyValueWhenAllowEmptyOptionIsTrue(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected an array of strings. Given ",test".');

        $context = new NormalizeValueContext();
        $context->setArrayAllowed(true);
        $context->setArrayDelimiter(',');
        $context->setResult(',test');
        $context->addOption('allow_empty', true);

        $this->processor->process($context);
    }

    public function testProcessForArraysForValueContainsOnlySpacesWhenAllowEmptyOptionIsTrue(): void
    {
        $context = new NormalizeValueContext();
        $context->setArrayAllowed(true);
        $context->setArrayDelimiter(',');
        $context->setResult('  ,test');
        $context->addOption('allow_empty', true);

        $this->processor->process($context);

        self::assertEquals('.+', $context->getRequirement());
        self::assertEquals(['  ', 'test'], $context->getResult());
    }

    public function testProcessForArrayContainsZeroValueWhenAllowEmptyOptionIsTrue(): void
    {
        $context = new NormalizeValueContext();
        $context->setArrayAllowed(true);
        $context->setArrayDelimiter(',');
        $context->setResult('0,false');
        $context->addOption('allow_empty', true);

        $this->processor->process($context);

        self::assertEquals('.+', $context->getRequirement());
        self::assertEquals(['0', 'false'], $context->getResult());
    }
}
