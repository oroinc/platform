<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition\CompleteEntityDefinitionHelper;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition\CompleteObjectDefinitionHelper;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use PHPUnit\Framework\MockObject\MockObject;

class CompleteDefinitionTest extends ConfigProcessorTestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private CompleteDefinition\CompleteEntityDefinitionHelper&MockObject $entityDefinitionHelper;
    private CompleteDefinition\CompleteObjectDefinitionHelper&MockObject $objectDefinitionHelper;
    private CompleteDefinition $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityDefinitionHelper = $this->createMock(CompleteEntityDefinitionHelper::class);
        $this->objectDefinitionHelper = $this->createMock(CompleteObjectDefinitionHelper::class);

        $this->processor = new CompleteDefinition(
            $this->doctrineHelper,
            $this->entityDefinitionHelper,
            $this->objectDefinitionHelper
        );
    }

    public function testProcessWhenConfigAlreadyCompleted(): void
    {
        $definition = new EntityDefinitionConfig();
        $definition->setExcludeNone();

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');
        $this->entityDefinitionHelper->expects(self::never())
            ->method('completeDefinition');
        $this->objectDefinitionHelper->expects(self::never())
            ->method('completeDefinition');

        $this->context->setProcessed(CompleteDefinition::OPERATION_NAME);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        self::assertTrue($this->context->isProcessed(CompleteDefinition::OPERATION_NAME));
        self::assertEquals(ConfigUtil::EXCLUSION_POLICY_NONE, $definition->getExclusionPolicy());
    }

    public function testProcessForManageableEntity(): void
    {
        $definition = new EntityDefinitionConfig();

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->entityDefinitionHelper->expects(self::once())
            ->method('completeDefinition')
            ->with(self::identicalTo($definition), self::identicalTo($this->context));
        $this->objectDefinitionHelper->expects(self::never())
            ->method('completeDefinition');

        $this->context->setResult($definition);
        $this->processor->process($this->context);

        self::assertTrue($this->context->isProcessed(CompleteDefinition::OPERATION_NAME));
        self::assertTrue($definition->isExcludeAll());
    }

    public function testProcessForNotManageableEntity(): void
    {
        $definition = new EntityDefinitionConfig();

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->entityDefinitionHelper->expects(self::never())
            ->method('completeDefinition')
            ->with(self::identicalTo($definition));
        $this->objectDefinitionHelper->expects(self::once())
            ->method('completeDefinition')
            ->with(self::identicalTo($definition), self::identicalTo($this->context));

        $this->context->setResult($definition);
        $this->processor->process($this->context);

        self::assertTrue($this->context->isProcessed(CompleteDefinition::OPERATION_NAME));
        self::assertTrue($definition->isExcludeAll());
    }
}
