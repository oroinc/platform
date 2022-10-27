<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\ConvertModelToEntity;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Util\EntityMapper;

class ConvertModelToEntityTest extends BatchUpdateProcessorTestCase
{
    /** @var ConvertModelToEntity */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new ConvertModelToEntity();
    }

    public function testProcessWhenNoBatchItems()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWhenHasErrors()
    {
        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('hasErrors')
            ->willReturn(true);
        $itemContext->expects(self::never())
            ->method('getTargetContext');

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoTargetContext()
    {
        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('hasErrors')
            ->willReturn(false);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn(null);

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoModel()
    {
        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $itemTargetContext = $this->createMock(CreateContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('hasErrors')
            ->willReturn(false);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($itemTargetContext);
        $itemTargetContext->expects(self::once())
            ->method('getResult')
            ->willReturn(null);
        $itemTargetContext->expects(self::never())
            ->method('getEntityMapper');
        $itemTargetContext->expects(self::never())
            ->method('getClassName');
        $itemTargetContext->expects(self::never())
            ->method('getConfig');
        $itemTargetContext->expects(self::never())
            ->method('setResult');

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoEntityMapper()
    {
        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $itemTargetContext = $this->createMock(CreateContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('hasErrors')
            ->willReturn(false);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($itemTargetContext);
        $itemTargetContext->expects(self::once())
            ->method('getResult')
            ->willReturn(new \stdClass());
        $itemTargetContext->expects(self::once())
            ->method('getEntityMapper')
            ->willReturn(null);
        $itemTargetContext->expects(self::never())
            ->method('getClassName');
        $itemTargetContext->expects(self::never())
            ->method('getConfig');
        $itemTargetContext->expects(self::never())
            ->method('setResult');

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenModelShouldBeConvertedToEntity()
    {
        $entityClass = Entity\User::class;
        $model = new \stdClass();
        $entity = new \stdClass();
        $config = new EntityDefinitionConfig();
        $entityMapper = $this->createMock(EntityMapper::class);

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $itemTargetContext = $this->createMock(CreateContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('hasErrors')
            ->willReturn(false);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($itemTargetContext);
        $itemTargetContext->expects(self::once())
            ->method('getResult')
            ->willReturn($model);
        $itemTargetContext->expects(self::once())
            ->method('getEntityMapper')
            ->willReturn($entityMapper);
        $itemTargetContext->expects(self::once())
            ->method('getClassName')
            ->willReturn($entityClass);
        $itemTargetContext->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);
        $entityMapper->expects(self::once())
            ->method('getEntity')
            ->with(self::identicalTo($model), $entityClass)
            ->willReturn($entity);
        $itemTargetContext->expects(self::once())
            ->method('setResult')
            ->with(self::identicalTo($entity));

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenModelShouldBeConvertedToEntityForApiResourceBasedOnManageableEntity()
    {
        $entityClass = Entity\UserProfile::class;
        $parentResourceClass = Entity\User::class;
        $model = new \stdClass();
        $entity = new \stdClass();
        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass($parentResourceClass);
        $entityMapper = $this->createMock(EntityMapper::class);

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $itemTargetContext = $this->createMock(CreateContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('hasErrors')
            ->willReturn(false);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($itemTargetContext);
        $itemTargetContext->expects(self::once())
            ->method('getResult')
            ->willReturn($model);
        $itemTargetContext->expects(self::once())
            ->method('getEntityMapper')
            ->willReturn($entityMapper);
        $itemTargetContext->expects(self::once())
            ->method('getClassName')
            ->willReturn($entityClass);
        $itemTargetContext->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);
        $entityMapper->expects(self::once())
            ->method('getEntity')
            ->with(self::identicalTo($model), $parentResourceClass)
            ->willReturn($entity);
        $itemTargetContext->expects(self::once())
            ->method('setResult')
            ->with(self::identicalTo($entity));

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }
}
