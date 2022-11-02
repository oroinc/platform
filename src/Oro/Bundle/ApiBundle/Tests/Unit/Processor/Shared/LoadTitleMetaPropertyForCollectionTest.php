<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadTitleMetaPropertyForCollection;
use Oro\Bundle\ApiBundle\Provider\EntityTitleProvider;
use Oro\Bundle\ApiBundle\Provider\ExpandedAssociationExtractor;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class LoadTitleMetaPropertyForCollectionTest extends GetListProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityTitleProvider */
    private $entityTitleProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExpandedAssociationExtractor */
    private $expandedAssociationExtractor;

    /** @var LoadTitleMetaPropertyForCollection */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityTitleProvider = $this->createMock(EntityTitleProvider::class);
        $this->expandedAssociationExtractor = $this->createMock(ExpandedAssociationExtractor::class);

        $this->processor = new LoadTitleMetaPropertyForCollection(
            $this->entityTitleProvider,
            $this->expandedAssociationExtractor,
            $this->configProvider
        );
    }

    /**
     * The all other tests are in LoadTitleMetaPropertyForSingleItemTest
     * @see \Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\LoadTitleMetaPropertyForSingleItemTest
     */
    public function testProcessForPrimaryEntityOnly()
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
    }
}
