<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Helper\UnidirectionalFieldHelper;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityConfigBundle\EventListener\EntityConfigStructureOptionsListener;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class EntityConfigStructureOptionsListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityConfigProvider;

    /** @var EntityConfigStructureOptionsListener */
    protected $listener;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->listener = new EntityConfigStructureOptionsListener($this->entityConfigProvider);
    }

    public function testOnOptionsRequest()
    {
        $fieldStructure = (new EntityFieldStructure())->setName('field');
        $entityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => \stdClass::class,
                'fields' => [$fieldStructure],
            ]
        );

        $this->entityConfigProvider
            ->expects($this->once())
            ->method('hasConfig')
            ->withConsecutive(
                [\stdClass::class],
                [\stdClass::class, 'field']
            )
            ->willReturn(true);

        $event = $this->getEntity(EntityStructureOptionsEvent::class, ['data' => [$entityStructure]]);
        $expectedFieldStructure = (clone $fieldStructure)->addOption('configurable', true);
        $expectedEntityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => \stdClass::class,
                'fields' => [$expectedFieldStructure],
            ]
        );

        $this->listener->onOptionsRequest($event);
        $this->assertEquals([$expectedEntityStructure], $event->getData());
    }

    public function testOnOptionsRequestUnidirectional()
    {
        $fieldName = sprintf('class%sfield', UnidirectionalFieldHelper::DELIMITER);
        $fieldStructure = (new EntityFieldStructure())->setName($fieldName);
        $entityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => \stdClass::class,
                'fields' => [$fieldStructure],
            ]
        );

        $this->entityConfigProvider
            ->expects($this->once())
            ->method('hasConfig')
            ->with('class', 'field')
            ->willReturn(true);

        $event = $this->getEntity(EntityStructureOptionsEvent::class, ['data' => [$entityStructure]]);
        $expectedFieldStructure = (clone $fieldStructure)->addOption('configurable', true);
        $expectedEntityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => \stdClass::class,
                'fields' => [$expectedFieldStructure],
            ]
        );

        $this->listener->onOptionsRequest($event);
        $this->assertEquals([$expectedEntityStructure], $event->getData());
    }
}
