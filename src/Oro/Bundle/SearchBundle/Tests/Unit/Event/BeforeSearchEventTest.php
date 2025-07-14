<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Event;

use Oro\Bundle\SearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use PHPUnit\Framework\TestCase;

class BeforeSearchEventTest extends TestCase
{
    public function testEvent(): void
    {
        $query = new Query();
        $event = new BeforeSearchEvent($query);
        $this->assertEquals($query, $event->getQuery());
        $anotherQuery = new Query();
        $anotherQuery->from('test');
        $event->setQuery($anotherQuery);
        $this->assertEquals($anotherQuery, $event->getQuery());
    }
}
