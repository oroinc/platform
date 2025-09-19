<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures;

/**
 * Test controller with attributes for ControllerListener tests
 */
#[TestAttribute('class_value', true)]
#[TestArrayAttribute('first', 'data1')]
#[TestArrayAttribute('second', 'data2')]
class AttributedTestController
{
    public function noAttributesAction()
    {
        return 'no attributes';
    }

    #[TestAttribute('method_value', false)]
    public function methodAttributeAction()
    {
        return 'method attribute';
    }

    #[TestArrayAttribute('method_first', 'method_data1')]
    #[TestArrayAttribute('method_second', 'method_data2')]
    public function methodArrayAttributeAction()
    {
        return 'method array attributes';
    }

    #[TestAttribute('override_value', false)]
    public function overrideAttributeAction()
    {
        return 'override attribute';
    }

    #[TestArrayAttribute('method_array', 'method_array_data')]
    public function mixedAttributeAction()
    {
        return 'mixed attributes';
    }
}
