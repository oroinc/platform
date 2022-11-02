<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\SearchBundle\Event\SearchQueryAwareEventInterface;
use Oro\Bundle\SearchBundle\EventListener\LimitResultsListener;
use Oro\Bundle\SearchBundle\Query\Query;

class LimitResultsListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LimitResultsListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new LimitResultsListener();
    }

    /**
     * @dataProvider onBeforeSearchDataProvider
     */
    public function testOnBeforeSearch(int $maxResults, int $expectedMaxResults): void
    {
        $query = new Query();
        $query->getCriteria()->setMaxResults($maxResults);

        $queryAwareEvent = $this->createMock(SearchQueryAwareEventInterface::class);
        $queryAwareEvent->expects(self::atLeastOnce())
            ->method('getQuery')
            ->willReturn($query);

        $this->listener->onBeforeSearch($queryAwareEvent);

        self::assertEquals($expectedMaxResults, $query->getCriteria()->getMaxResults());
    }

    public function onBeforeSearchDataProvider(): array
    {
        return [
            'hard limit applied if max results is zero' => [
                'maxResults' => 0,
                'expectedMaxResults' => 1000
            ],
            'hard limit applied if max results is greater than 1000' => [
                'maxResults' => 1001,
                'expectedMaxResults' => 1000
            ],
            'hard limit is not applied if max results is less than 1000' => [
                'maxResults' => 999,
                'expectedMaxResults' => 999
            ],
        ];
    }
}
