<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\EventListener;

use Oro\Bundle\DraftBundle\EventListener\SearchListener;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

class SearchListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getPrepareEntityMapEvent
     */
    public function testPrepareEntityMapEvent(object $entity, array $data, array $expectedData): void
    {
        $searchListener = new SearchListener();
        $event = new PrepareEntityMapEvent($entity, get_class($entity), $data, []);

        $searchListener->prepareEntityMapEvent($event);

        $this->assertEquals($expectedData, $event->getData());
    }

    public function getPrepareEntityMapEvent(): array
    {
        $draft = new DraftableEntityStub();
        $draft->setDraftUuid(UUIDGenerator::v4());

        $data = [
            'text' => ['name' => 'ORO']
        ];

        return [
            'not draft' => [
                'entity' => new DraftableEntityStub(),
                'data' => $data,
                'expectedData' => $data
            ],
            'draft' => [
                'entity' => $draft,
                'data' => $data,
                'expectedData' => []
            ],
        ];
    }
}
