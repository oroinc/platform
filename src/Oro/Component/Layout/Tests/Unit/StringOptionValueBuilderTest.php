<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Exception\UnexpectedTypeException;
use Oro\Component\Layout\StringOptionValueBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class StringOptionValueBuilderTest extends TestCase
{
    public function testConstructWithInvalidDelimiter(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        new StringOptionValueBuilder(null);
    }

    public function testBuildWithDefaultOptions(): void
    {
        $builder = new StringOptionValueBuilder();
        $builder->add('val1');
        $builder->add('val2');
        $builder->add('val3 val4');
        $builder->remove('val2');
        $builder->replace('val3', 'replaced_val3');
        $this->assertEquals('val1 replaced_val3 val4', $builder->get());
    }

    public function testBuildWithoutTokenize(): void
    {
        $builder = new StringOptionValueBuilder(' ', false);
        $builder->add('val1');
        $builder->add('val2');
        $builder->add('val3 val4');
        $builder->remove('val2');
        $builder->replace('val3', 'replaced_val3');
        $this->assertEquals('val1 val3 val4', $builder->get());
    }

    public function testBuildWithCustomDelimiter(): void
    {
        $builder = new StringOptionValueBuilder(',');
        $builder->add('val1');
        $builder->add('val2');
        $builder->add('val3,val4');
        $builder->remove('val2');
        $builder->replace('val3', 'replaced_val3');
        $this->assertEquals('val1,replaced_val3,val4', $builder->get());
    }

    public function testBuildWithoutDelimiter(): void
    {
        $builder = new StringOptionValueBuilder('');
        $builder->add('val1');
        $builder->add('val2');
        $builder->add('val3');
        $builder->remove('val2');
        $builder->replace('val3', 'replaced_val3');
        $this->assertEquals('val1replaced_val3', $builder->get());
    }

    public function testAddThrowsExceptionIfInvalidValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $builder = new StringOptionValueBuilder();
        $builder->add(123);
    }

    public function testRemoveThrowsExceptionIfInvalidValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $builder = new StringOptionValueBuilder();
        $builder->remove(123);
    }

    public function testReplaceThrowsExceptionIfInvalidOldValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $builder = new StringOptionValueBuilder();
        $builder->replace(123, 'new');
    }

    public function testReplaceThrowsExceptionIfInvalidNewValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $builder = new StringOptionValueBuilder();
        $builder->replace('old', 123);
    }

    public function testReplaceWithNullNewValue(): void
    {
        $builder = new StringOptionValueBuilder();
        $builder->add('val1');
        $builder->add('val2');
        $builder->replace('val1', null);
        $this->assertEquals('val2', $builder->get());
    }

    public function testAddWithEmptyValue(): void
    {
        $builder = new StringOptionValueBuilder();
        $builder->add('');
        $this->assertEquals('', $builder->get());
    }

    public function testRemoveWithEmptyValue(): void
    {
        $builder = new StringOptionValueBuilder();
        $builder->add('val1');
        $builder->remove('');
        $this->assertEquals('val1', $builder->get());
    }

    public function testReplaceWithEmptyOldValue(): void
    {
        $builder = new StringOptionValueBuilder();
        $builder->add('val1');
        $builder->remove('');
        $this->assertEquals('val1', $builder->get());
    }

    public function testReplaceWithEmptyNewValue(): void
    {
        $builder = new StringOptionValueBuilder();
        $builder->add('val1');
        $builder->add('val2');
        $builder->remove('val1');
        $this->assertEquals('val2', $builder->get());
    }
}
