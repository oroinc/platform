<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\ArrayOptionValueBuilder;
use Oro\Component\Layout\Exception\InvalidArgumentException;

class ArrayOptionValueBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildWithDefaultOptions()
    {
        $builder = new ArrayOptionValueBuilder();
        $builder->add(['val1']);
        $builder->add(['val2']);
        $builder->add(['val3', 'val4']);
        $builder->remove(['val2']);
        $builder->replace(['val3'], ['replaced_val3']);
        $this->assertEquals(['val1', 'replaced_val3', 'val4'], $builder->get());
    }

    public function testBuildWithAllowedScalarValues()
    {
        $builder = new ArrayOptionValueBuilder(true);
        $builder->add('val1');
        $builder->add(['val2']);
        $builder->add(['val3', 'val4']);
        $builder->remove('val2');
        $builder->replace(['val3'], ['replaced_val3']);
        $this->assertEquals(['val1', 'replaced_val3', 'val4'], $builder->get());
    }

    public function testRemoveWithDifferentTypes()
    {
        $builder = new ArrayOptionValueBuilder();
        $builder->add(['val1']);
        $builder->add([['val2']]);
        $builder->remove([['val2']]);
        $this->assertEquals(['val1'], $builder->get());
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     */
    public function testAddThrowsExceptionIfInvalidValue()
    {
        $builder = new ArrayOptionValueBuilder();
        $builder->add(123);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     */
    public function testRemoveThrowsExceptionIfInvalidValue()
    {
        $builder = new ArrayOptionValueBuilder();
        $builder->remove(123);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     */
    public function testReplaceThrowsExceptionIfInvalidOldValue()
    {
        $builder = new ArrayOptionValueBuilder();
        $builder->replace(123, ['new']);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     */
    public function testReplaceThrowsExceptionIfInvalidNewValue()
    {
        $builder = new ArrayOptionValueBuilder();
        $builder->replace(['old'], 123);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testReplaceThrowsExceptionIfCountNotEqual()
    {
        $builder = new ArrayOptionValueBuilder();
        $builder->replace(['old'], ['new1', 'new2']);
    }

    public function testAddWithEmptyValue()
    {
        $builder = new ArrayOptionValueBuilder();
        $builder->add(['']);
        $this->assertEquals([''], $builder->get());
    }

    public function testRemoveWithEmptyValue()
    {
        $builder = new ArrayOptionValueBuilder();
        $builder->add(['val1']);
        $builder->remove(['']);
        $this->assertEquals(['val1'], $builder->get());
    }

    public function testReplaceWithEmptyOldValue()
    {
        $builder = new ArrayOptionValueBuilder();
        $builder->add(['val1']);
        $builder->replace([''], ['new']);
        $this->assertEquals(['val1'], $builder->get());
    }

    public function testReplaceWithEmptyNewValue()
    {
        $builder = new ArrayOptionValueBuilder();
        $builder->add(['val1']);
        $builder->add(['val2']);
        $builder->replace(['val1'], ['']);
        $this->assertEquals(['', 'val2'], $builder->get());
    }
}
