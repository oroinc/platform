<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Form\DataTransformer\EntityFieldFallbackTransformer;

class EntityFieldFallbackTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityFieldFallbackTransformer
     */
    protected $entityFieldFallbackTransformer;

    protected function setUp()
    {
        $this->entityFieldFallbackTransformer = new EntityFieldFallbackTransformer();
    }

    public function testTransformReturnsValueIfNotFallbackType()
    {
        $this->assertEquals('testValue', $this->entityFieldFallbackTransformer->transform('testValue'));
    }

    public function testTransformSetsUseFallbackOption()
    {
        $value = new EntityFieldFallbackValue();
        $value->setFallback('testFallback');
        $value = $this->entityFieldFallbackTransformer->transform($value);
        $this->assertTrue($value->isUseFallback());
    }

    public function testTransformSetsScalarValueIfArray()
    {
        $value = new EntityFieldFallbackValue();
        $testValue = ['test' => 'test'];
        $value->setArrayValue($testValue);
        $value = $this->entityFieldFallbackTransformer->transform($value);
        $this->assertSame($testValue, $value->getScalarValue());
    }

    public function testTransformSetsScalarValueIfScalar()
    {
        $value = new EntityFieldFallbackValue();
        $testValue = 'testValue';
        $value->setScalarValue($testValue);
        $value = $this->entityFieldFallbackTransformer->transform($value);
        $this->assertSame($testValue, $value->getScalarValue());
    }

    public function testReverseTransformClearsOwnValues()
    {
        $value = new EntityFieldFallbackValue();
        $value->setScalarValue('testValue');
        $value->setArrayValue(['testValue']);
        $value->setUseFallback(true);
        $value->setFallback('testFallback');

        $value = $this->entityFieldFallbackTransformer->reverseTransform($value);
        $this->assertNull($value->getScalarValue());
        $this->assertNull($value->getArrayValue());
        $this->assertNull($value->getOwnValue());
    }

    public function testReverseTransformClearsFallbackAndArrayIfScalar()
    {
        $value = new EntityFieldFallbackValue();
        $scalarValue = 'testValue';
        $value->setScalarValue($scalarValue);
        $value->setArrayValue(['testValue']);
        $value->setUseFallback(false);
        $value->setFallback('testFallback');

        $value = $this->entityFieldFallbackTransformer->reverseTransform($value);
        $this->assertNull($value->getFallback());
        $this->assertEquals($scalarValue, $value->getScalarValue());
        $this->assertNull($value->getArrayValue());
        $this->assertNotNull($value->getOwnValue());
    }

    public function testReverseTransformClearsFallbackAndArrayIfArray()
    {
        $value = new EntityFieldFallbackValue();
        $arrayValue = ['testValue'];
        $value->setScalarValue($arrayValue);
        $value->setUseFallback(false);
        $value->setFallback('testFallback');

        $value = $this->entityFieldFallbackTransformer->reverseTransform($value);
        $this->assertNull($value->getFallback());
        $this->assertEquals($arrayValue, $value->getArrayValue());
        $this->assertNull($value->getScalarValue());
        $this->assertEquals($arrayValue, $value->getOwnValue());
    }
}
