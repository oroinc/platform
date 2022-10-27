<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentEntityChoiceType;

class SegmentEntityChoiceTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var SegmentEntityChoiceType */
    private $type;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $entityProviderMock;

    protected function setUp(): void
    {
        $this->entityProviderMock = $this->createMock(EntityProvider::class);

        $this->type = new SegmentEntityChoiceType($this->entityProviderMock);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_segment_entity_choice', $this->type->getName());
        $this->assertInstanceOf(EntityChoiceType::class, $this->type);
    }
}
