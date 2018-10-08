<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentEntityChoiceType;

class SegmentEntityChoiceTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var SegmentEntityChoiceType */
    protected $type;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityProviderMock;

    protected function setUp()
    {
        $this->entityProviderMock = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new SegmentEntityChoiceType($this->entityProviderMock);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_segment_entity_choice', $this->type->getName());
        $this->assertTrue($this->type instanceof EntityChoiceType);
    }
}
