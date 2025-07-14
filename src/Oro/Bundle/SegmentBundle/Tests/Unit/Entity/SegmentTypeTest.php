<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Entity;

use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use PHPUnit\Framework\TestCase;

class SegmentTypeTest extends TestCase
{
    private const TEST_NAME = 'name_test';

    private SegmentType $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->entity = new SegmentType(self::TEST_NAME);
    }

    public function testGettersAndSetters(): void
    {
        $this->assertEquals(self::TEST_NAME, $this->entity->getName());

        $testLabel = 'label_test';
        $this->assertNull($this->entity->getLabel());

        $this->entity->setLabel($testLabel);
        $this->assertEquals($testLabel, $this->entity->getLabel());
        $this->assertEquals($testLabel, (string)$this->entity);
    }
}
