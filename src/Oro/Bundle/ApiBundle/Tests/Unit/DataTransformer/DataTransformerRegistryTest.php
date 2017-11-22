<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DataTransformer;

use Oro\Component\EntitySerializer\DataTransformerInterface;
use Oro\Bundle\ApiBundle\DataTransformer\DataTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;

class DataTransformerRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|DataTransformerInterface */
    protected $transformer1;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DataTransformerInterface */
    protected $transformer2;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DataTransformerInterface */
    protected $transformer3;

    /** @var DataTransformerRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->transformer1 = $this->createMock(DataTransformerInterface::class);
        $this->transformer2 = $this->createMock(DataTransformerInterface::class);
        $this->transformer3 = $this->createMock(DataTransformerInterface::class);

        $this->registry = new DataTransformerRegistry(
            [
                'dataType1' => [
                    [$this->transformer2, 'rest'],
                    [$this->transformer1, null]
                ],
                'dataType2' => [
                    [$this->transformer3, 'rest']
                ],
            ],
            new RequestExpressionMatcher()
        );
    }

    public function testGetDataTransformerWhenItExistsForSpecificRequestType()
    {
        self::assertSame(
            $this->transformer2,
            $this->registry->getDataTransformer('dataType1', new RequestType(['rest', 'json_api']))
        );
    }

    public function testGetDataTransformerWhenItDoesNotExistForSpecificRequestTypeButExistsForAnyRequestType()
    {
        self::assertSame(
            $this->transformer1,
            $this->registry->getDataTransformer('dataType1', new RequestType(['another']))
        );
    }

    public function testGetDataTransformerWhenItDoesNotExistForSpecificRequestTypeAndDoesNotExistForAnyRequestType()
    {
        self::assertNull(
            $this->registry->getDataTransformer('dataType2', new RequestType(['another']))
        );
    }

    public function testGetDataTransformerForDataTypeWithoutTransformer()
    {
        self::assertNull(
            $this->registry->getDataTransformer('undefined', new RequestType(['rest', 'json_api']))
        );
    }
}
