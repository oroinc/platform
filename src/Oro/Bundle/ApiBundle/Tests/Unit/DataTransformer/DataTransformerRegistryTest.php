<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DataTransformer;

use Oro\Bundle\ApiBundle\DataTransformer\DataTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\EntitySerializer\DataTransformerInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class DataTransformerRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DataTransformerInterface */
    private $transformer1;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DataTransformerInterface */
    private $transformer2;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DataTransformerInterface */
    private $transformer3;

    /** @var DataTransformerRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->transformer1 = $this->createMock(DataTransformerInterface::class);
        $this->transformer2 = $this->createMock(DataTransformerInterface::class);
        $this->transformer3 = $this->createMock(DataTransformerInterface::class);

        $this->registry = new DataTransformerRegistry(
            [
                'dataType1' => [
                    ['transformer2', 'rest'],
                    ['transformer1', null]
                ],
                'dataType2' => [
                    ['transformer3', 'rest']
                ]
            ],
            TestContainerBuilder::create()
                ->add('transformer1', $this->transformer1)
                ->add('transformer2', $this->transformer2)
                ->add('transformer3', $this->transformer3)
                ->getContainer($this),
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
