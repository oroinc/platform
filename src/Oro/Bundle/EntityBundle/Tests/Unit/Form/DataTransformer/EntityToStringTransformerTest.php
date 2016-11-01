<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\EntityBundle\Form\DataTransformer\EntityToStringTransformer;
use Oro\Bundle\SalesBundle\Tests\Unit\Stub\Customer1;
use Oro\Bundle\SalesBundle\Tests\Unit\Stub\Customer2;

class EntityToStringTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityToStringTransformer */
    protected $transformer;

    public function setUp()
    {
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->will($this->returnCallback(function ($entity) {
                if ($entity instanceof Customer1) {
                    return 1;
                }

                if ($entity instanceof Customer2) {
                    return 2;
                }

                return null;
            }));
        $doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->will($this->returnCallback(function ($entityClass) {
                return new $entityClass();
            }));

        $this->transformer = new EntityToStringTransformer($doctrineHelper);
    }

    /**
     * @dataProvider transformProvider
     */
    public function testTransform($value, $expectedValue)
    {
        $this->assertEquals($expectedValue, $this->transformer->transform($value));
    }

    public function transformProvider()
    {
        return [
            [
                null,
                null,
            ],
            [
                new Customer1(),
                json_encode([
                    'entityClass' => 'Oro\Bundle\SalesBundle\Tests\Unit\Stub\Customer1',
                    'entityId'    => 1,
                ]),
            ],
            [
                new Customer2(),
                json_encode([
                    'entityClass' => 'Oro\Bundle\SalesBundle\Tests\Unit\Stub\Customer2',
                    'entityId'    => 2,
                ]),
            ],
        ];
    }

    /**
     * @dataProvider reverseTransformProvider
     */
    public function testReverseTransform($value, $expectedValue)
    {
        $this->assertEquals($expectedValue, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformProvider()
    {
        return [
            [
                null,
                null,
            ],
            [
                json_encode([
                    'entityClass' => 'Oro\Bundle\SalesBundle\Tests\Unit\Stub\Customer1',
                    'entityId'    => 1,
                ]),
                new Customer1(),
            ],
            [
                json_encode([
                    'entityClass' => 'Oro\Bundle\SalesBundle\Tests\Unit\Stub\Customer2',
                    'entityId'    => 2,
                ]),
                new Customer2(),
            ],
        ];
    }
}
