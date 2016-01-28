<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetFieldConfig;

use Oro\Bundle\ApiBundle\Processor\Config\GetFieldConfig\FieldConfigContext;

class FieldConfigContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var FieldConfigContext */
    protected $context;

    protected function setUp()
    {
        $this->context = new FieldConfigContext();
    }

    public function testFieldName()
    {
        $this->assertNull($this->context->getFieldName());

        $this->context->setFieldName('test');
        $this->assertEquals('test', $this->context->getFieldName());
        $this->assertEquals('test', $this->context->get(FieldConfigContext::FIELD_NAME));
    }
}
