<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentEntityChoiceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SegmentEntityChoiceTypeTest extends TestCase
{
    private EntityProvider&MockObject $entityProviderMock;
    private SegmentEntityChoiceType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityProviderMock = $this->createMock(EntityProvider::class);

        $this->type = new SegmentEntityChoiceType($this->entityProviderMock);
    }

    public function testGetName(): void
    {
        $this->assertEquals('oro_segment_entity_choice', $this->type->getName());
        $this->assertInstanceOf(EntityChoiceType::class, $this->type);
    }
}
