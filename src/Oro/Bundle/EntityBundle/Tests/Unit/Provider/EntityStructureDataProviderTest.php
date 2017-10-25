<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider;
use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityStructureDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var EntityWithFieldsProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityWithFieldsProvider;

    /** @var EntityClassNameHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $classNameHelper;

    /** @var EntityStructureDataProvider */
    protected $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->entityWithFieldsProvider = $this->createMock(EntityWithFieldsProvider::class);

        $this->classNameHelper = $this->getMockBuilder(EntityClassNameHelper::class)
            ->setMethods(['resolveEntityClass'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new EntityStructureDataProvider(
            $this->eventDispatcher,
            $this->entityWithFieldsProvider,
            $this->classNameHelper
        );
    }

    public function testGetData()
    {
        $event = new EntityStructureOptionsEvent();

        $field = new EntityFieldStructure();
        $field->setName('field1')
            ->setLabel('field_label')
            ->setRelationType(RelationType::MANY_TO_MANY)
            ->setType('type')
            ->setRelatedEntityName('SomeNamespace\SomeRelatedEntity');

        $entityStructure = new EntityStructure();
        $entityStructure->setClassName('SomeNamespace\SomeClass')
            ->setId('SomeNamespace_SomeClass')
            ->setFields([$field])
            ->setIcon('icon')
            ->setRoutes(['viewRoute' => 'some_route']);

        $event->setData([$entityStructure->getClassName() => $entityStructure]);

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(EntityStructureOptionsEvent::EVENT_NAME, $event)
            ->willReturn($event);

        $this->entityWithFieldsProvider
            ->expects($this->once())
            ->method('getFields')
            ->with(true, true, true, false, true, true)
            ->willReturn([
                [
                    'name' => 'SomeNamespace\SomeClass',
                    'icon' => 'icon',
                    'routes' => ['viewRoute' => 'some_route'],
                    'fields' => [[
                        'name' => 'field1',
                        'type' => 'type',
                        'label' => 'field_label',
                        'relation_type' => RelationType::MANY_TO_MANY,
                        'related_entity_name' => 'SomeNamespace\SomeRelatedEntity',
                    ]],
                ],
            ]);

        self::assertEquals([$entityStructure->getClassName() => $entityStructure], $this->provider->getData());
    }
}
