<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
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

    protected function setUp()
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

    public function testProcessForAlreadyProcessedConfig()
    {
        $config = [
            'exclusion_policy' => 'all'
        ];

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');
        $this->entityDefinitionHelper->expects(self::never())
            ->method('completeDefinition');
        $this->objectDefinitionHelper->expects(self::never())
            ->method('completeDefinition');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
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

        self::assertTrue($definition->isExcludeAll());
    }
}
