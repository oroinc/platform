<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Duplicator\Filter;

use Oro\Bundle\DraftBundle\Duplicator\Filter\SourceFilter;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Component\Testing\Unit\EntityTrait;

class SourceFilterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testApplyWithoutSource(): void
    {
        /** @var DraftableInterface $draftSource */
        $source = $this->getEntity(DraftableEntityStub::class, ['id' => 1]);
        $filter = new SourceFilter($source);
        $object = new DraftableEntityStub();

        $filter->apply($object, 'draftSource', null);
        $this->assertSame($source, $object->getDraftSource());
    }

    public function testApplyWithSource(): void
    {
        /** @var DraftableInterface $draftSource */
        $source = $this->getEntity(DraftableEntityStub::class, ['id' => 2]);
        /** @var DraftableInterface $draftSource */
        $draftSource = $this->getEntity(DraftableEntityStub::class, ['id' => 1]);
        $filter = new SourceFilter($source);
        $object = new DraftableEntityStub();
        $object->setDraftSource($draftSource);

        $filter->apply($object, 'draftSource', null);
        $this->assertSame($draftSource, $object->getDraftSource());
        $this->assertNotSame($source, $object->getDraftSource());
    }
}
