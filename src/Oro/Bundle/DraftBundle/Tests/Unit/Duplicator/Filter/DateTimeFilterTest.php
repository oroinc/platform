<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Duplicator\Filter;

use Oro\Bundle\DraftBundle\Duplicator\Filter\DateTimeFilter;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Component\Testing\Unit\EntityTrait;

class DateTimeFilterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testApply(): void
    {
        $dateTime = new \DateTime('2001-01-01', new \DateTimeZone('UTC'));
        $source = $this->getEntity(
            DraftableEntityStub::class,
            ['createdAt' => $dateTime]
        );
        $filter = new DateTimeFilter();
        $filter->apply($source, 'createdAt', null);

        $this->assertGreaterThan($dateTime, $source->getCreatedAt());
    }
}
