<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Duplicator\Filter;

use Oro\Bundle\DraftBundle\Duplicator\Filter\UuidFilter;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use PHPUnit\Framework\TestCase;

class UuidFilterTest extends TestCase
{
    private UuidFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->filter = new UuidFilter();
    }

    public function testApplyWithUuid(): void
    {
        $object = new DraftableEntityStub();
        $property = 'draftUuid';

        $this->filter->apply($object, $property, null);
        $this->assertNotNull($object->getDraftUuid());
        $this->assertMatchesRegularExpression(
            '/^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}$/',
            $object->getDraftUuid()
        );
    }
}
