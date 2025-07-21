<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\ArrayOptionValueBuilder;
use Oro\Component\Layout\Exception\InvalidArgumentException;
use Oro\Component\Layout\Exception\UnexpectedTypeException;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ArrayOptionValueBuilderTest extends TestCase
{
    public function testBuildWithDefaultOptions(): void
    {
        $builder = new ArrayOptionValueBuilder();
        $builder->add(['val1']);
        $builder->add(['val2']);
        $builder->add(['val3', 'val4']);
        $builder->remove(['val2']);
        $builder->replace(['val3'], ['replaced_val3']);
        $this->assertEquals(['val1', 'replaced_val3', 'val4'], $builder->get());
    }

    public function testBuildWithAllowedScalarValues(): void
    {
        $builder = new ArrayOptionValueBuilder(true);
        $builder->add('val1');
        $builder->add(['val2']);
        $builder->add(['val3', 'val4']);
        $builder->remove('val2');
        $builder->replace(['val3'], ['replaced_val3']);
        $this->assertEquals(['val1', 'replaced_val3', 'val4'], $builder->get());
    }

    public function testRemoveWithDifferentTypes(): void
    {
        $builder = new ArrayOptionValueBuilder();
        $builder->add(['val1']);
        $builder->add([['val2']]);
        $builder->remove([['val2']]);
        $this->assertEquals(['val1'], $builder->get());
    }

    public function testAddThrowsExceptionIfInvalidValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $builder = new ArrayOptionValueBuilder();
        $builder->add(123);
    }

    public function testRemoveThrowsExceptionIfInvalidValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $builder = new ArrayOptionValueBuilder();
        $builder->remove(123);
    }

    public function testReplaceThrowsExceptionIfInvalidOldValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $builder = new ArrayOptionValueBuilder();
        $builder->replace(123, ['new']);
    }

    public function testReplaceThrowsExceptionIfInvalidNewValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $builder = new ArrayOptionValueBuilder();
        $builder->replace(['old'], 123);
    }

    public function testReplaceThrowsExceptionIfCountNotEqual(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $builder = new ArrayOptionValueBuilder();
        $builder->replace(['old'], ['new1', 'new2']);
    }

    public function testAddWithEmptyValue(): void
    {
        $builder = new ArrayOptionValueBuilder();
        $builder->add(['']);
        $this->assertEquals([''], $builder->get());
    }

    public function testRemoveWithEmptyValue(): void
    {
        $builder = new ArrayOptionValueBuilder();
        $builder->add(['val1']);
        $builder->remove(['']);
        $this->assertEquals(['val1'], $builder->get());
    }

    public function testReplaceWithEmptyOldValue(): void
    {
        $builder = new ArrayOptionValueBuilder();
        $builder->add(['val1']);
        $builder->replace([''], ['new']);
        $this->assertEquals(['val1'], $builder->get());
    }

    public function testReplaceWithEmptyNewValue(): void
    {
        $builder = new ArrayOptionValueBuilder();
        $builder->add(['val1']);
        $builder->add(['val2']);
        $builder->replace(['val1'], ['']);
        $this->assertEquals(['', 'val2'], $builder->get());
    }
}
