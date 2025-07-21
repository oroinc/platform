<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Helper\UnidirectionalFieldHelper;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityConfigBundle\EventListener\EntityConfigStructureOptionsListener;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityConfigStructureOptionsListenerTest extends TestCase
{
    use EntityTrait;

    private ConfigProvider&MockObject $entityConfigProvider;
    private EntityConfigStructureOptionsListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->listener = new EntityConfigStructureOptionsListener($this->entityConfigProvider);
    }

    public function testOnOptionsRequest(): void
    {
        $fieldStructure = new EntityFieldStructure();
        $fieldStructure->setName('field');
        $entityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => \stdClass::class,
                'fields' => [$fieldStructure],
            ]
        );

        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->withConsecutive(
                [\stdClass::class],
                [\stdClass::class, 'field']
            )
            ->willReturn(true);

        $event = $this->getEntity(EntityStructureOptionsEvent::class, ['data' => [$entityStructure]]);
        $expectedFieldStructure = clone $fieldStructure;
        $expectedFieldStructure->addOption('configurable', true);
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

    public function testOnOptionsRequestUnidirectional(): void
    {
        $fieldName = sprintf('class%sfield', UnidirectionalFieldHelper::DELIMITER);
        $fieldStructure = new EntityFieldStructure();
        $fieldStructure->setName($fieldName);
        $entityStructure = $this->getEntity(
            EntityStructure::class,
            [
                'className' => \stdClass::class,
                'fields' => [$fieldStructure],
            ]
        );

        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('class', 'field')
            ->willReturn(true);

        $event = $this->getEntity(EntityStructureOptionsEvent::class, ['data' => [$entityStructure]]);
        $expectedFieldStructure = clone $fieldStructure;
        $expectedFieldStructure->addOption('configurable', true);
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
