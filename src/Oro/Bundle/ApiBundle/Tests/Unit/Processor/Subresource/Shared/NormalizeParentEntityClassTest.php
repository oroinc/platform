<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\NormalizeParentEntityClass;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;

class NormalizeParentEntityClassTest extends GetSubresourceProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ResourcesProvider */
    private $resourcesProvider;

    /** @var NormalizeParentEntityClass */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);

        $this->processor = new NormalizeParentEntityClass(
            $this->valueNormalizer,
            $this->resourcesProvider
        );
    }

    public function testProcessWhenParentClassNameIsNotSet()
    {
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(
                    'entity type constraint',
                    'The parent entity class must be set in the context.'
                )
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenParentClassAlreadyNormalized()
    {
        $this->context->setParentClassName('Test\Class');

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $this->processor->process($this->context);
    }

    public function testConvertParentEntityTypeToEntityClass()
    {
        $this->context->setParentClassName('test');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($this->context->getParentClassName(), DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn('Test\Class');
        $this->resourcesProvider->expects(self::once())
            ->method('isResourceAccessible')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(true);

        $this->processor->process($this->context);

        self::assertSame('Test\Class', $this->context->getParentClassName());
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\ResourceNotAccessibleException
     */
    public function testProcessForNotAccessibleParentEntityType()
    {
        $this->context->setParentClassName('test');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($this->context->getParentClassName(), DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn('Test\Class');
        $this->resourcesProvider->expects(self::once())
            ->method('isResourceAccessible')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(false);

        $this->processor->process($this->context);
    }

    public function testProcessForInvalidParentEntityType()
    {
        $this->context->setParentClassName('test');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($this->context->getParentClassName(), DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willThrowException(new \Exception('some error'));

        $this->processor->process($this->context);

        self::assertNull($this->context->getParentClassName());
        self::assertEquals(
            [
                Error::createValidationError('entity type constraint', 'Unknown parent entity type: test.', 404)
            ],
            $this->context->getErrors()
        );
    }
}
