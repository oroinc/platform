<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class CompleteDefinitionTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CompleteDefinition\CompleteEntityDefinitionHelper */
    private $entityDefinitionHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CompleteDefinition\CompleteObjectDefinitionHelper */
    private $objectDefinitionHelper;

    /** @var CompleteDefinition */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityDefinitionHelper = $this->createMock(CompleteDefinition\CompleteEntityDefinitionHelper::class);
        $this->objectDefinitionHelper = $this->createMock(CompleteDefinition\CompleteObjectDefinitionHelper::class);

        $this->processor = new CompleteDefinition(
            $this->doctrineHelper,
            $this->entityDefinitionHelper,
            $this->objectDefinitionHelper
        );
    }

    public function testProcessWhenConfigAlreadyCompleted()
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

    public function testProcessForManageableEntity()
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

    public function testProcessForNotManageableEntity()
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
