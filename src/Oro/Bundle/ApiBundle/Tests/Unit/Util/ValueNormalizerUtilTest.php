<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

class ValueNormalizerUtilTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider humanizeClassNameDataProvider
     */
    public function testHumanizeClassName($className, $classSuffix, $expected)
    {
        $this->assertEquals(
            $expected,
            ValueNormalizerUtil::humanizeClassName($className, $classSuffix)
        );
    }

    public function humanizeClassNameDataProvider()
    {
        return [
            ['\Exception', null, 'exception'],
            ['\Exception', 'Exception', 'exception'],
            ['\LogicException', null, 'logic exception'],
            ['\LogicException', 'Exception', 'logic exception'],
            ['\InvalidArgumentException', null, 'invalid argument exception'],
            ['\InvalidArgumentException', 'Exception', 'invalid argument exception'],
            ['Test\InvalidArgumentException', null, 'invalid argument exception'],
            ['Test\InvalidArgumentException', 'Exception', 'invalid argument exception'],
        ];
    }

    public function testConvertToEntityType()
    {
        $entityClass = 'Test\Class';
        $entityType = 'test_class';
        $requestType = new RequestType([RequestType::REST]);

        $valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();
        $valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with($entityClass, DataType::ENTITY_TYPE, $requestType)
            ->willReturn($entityType);

        $this->assertEquals(
            $entityType,
            ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityClass, $requestType)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConvertToEntityTypeWhenExceptionOccurred()
    {
        $entityClass = 'Test\Class';
        $requestType = new RequestType([RequestType::REST]);

        $valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();
        $valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with($entityClass, DataType::ENTITY_TYPE, $requestType)
            ->willThrowException(new \InvalidArgumentException());

        ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityClass, $requestType);
    }

    public function testConvertToEntityTypeWhenIgnoreException()
    {
        $entityClass = 'Test\Class';
        $requestType = new RequestType([RequestType::REST]);

        $valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();
        $valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with($entityClass, DataType::ENTITY_TYPE, $requestType)
            ->willThrowException(new \InvalidArgumentException());

        $this->assertNull(
            ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityClass, $requestType, false)
        );
    }
}
