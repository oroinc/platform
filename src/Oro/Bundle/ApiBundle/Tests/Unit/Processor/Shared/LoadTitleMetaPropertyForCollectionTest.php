<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadTitleMetaProperty;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadTitleMetaPropertyForCollection;
use Oro\Bundle\ApiBundle\Provider\EntityTitleProviderInterface;
use Oro\Bundle\ApiBundle\Provider\ExpandedAssociationExtractor;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class LoadTitleMetaPropertyForCollectionTest extends GetListProcessorTestCase
{
    private EntityTitleProviderInterface&MockObject $entityTitleProvider;
    private ExpandedAssociationExtractor&MockObject $expandedAssociationExtractor;
    private LoadTitleMetaPropertyForCollection $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityTitleProvider = $this->createMock(EntityTitleProviderInterface::class);
        $this->expandedAssociationExtractor = $this->createMock(ExpandedAssociationExtractor::class);

        $this->processor = new LoadTitleMetaPropertyForCollection(
            $this->entityTitleProvider,
            $this->expandedAssociationExtractor,
            $this->configProvider
        );
    }

    /**
     * The all other tests are in {@see LoadTitleMetaPropertyForSingleItemTest}
     */
    public function testProcessForPrimaryEntityOnly(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $config->addField('id')->setDataType('integer');
        $titleField = $config->addField('__title__');
        $titleField->setMetaProperty(true);
        $titleField->setMetaPropertyResultName('title');

        $data = [
            ['id' => 123]
        ];

        $titles = [
            ['entity' => 'Test\Entity', 'id' => 123, 'title' => 'title 123']
        ];

        $identifierMap = [
            'Test\Entity' => ['id', [123]]
        ];

        $this->expandedAssociationExtractor->expects(self::never())
            ->method('getExpandedAssociations');
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with($identifierMap)
            ->willReturn($titles);

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->setResult($data);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                ['id' => 123, '__title__' => 'title 123']
            ],
            $this->context->getResult()
        );
        self::assertTrue($this->context->isProcessed(LoadTitleMetaProperty::OPERATION_NAME));
    }
}
