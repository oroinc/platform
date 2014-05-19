<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Entity;

use Oro\Bundle\SegmentBundle\Entity\SegmentType;

class SegmentTypeTest extends \PHPUnit_Framework_TestCase
{
    const TEST_NAME = 'name_test';

    /** @var SegmentType */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new SegmentType(self::TEST_NAME);
    }

    protected function tearDown()
    {
        unset($this->entity);
    }

    public function testGettersAndSetters()
    {
        $this->assertEquals(self::TEST_NAME, $this->entity->getName());

        $testLabel = 'label_test';
        $this->assertNull($this->entity->getLabel());

        $this->entity->setLabel($testLabel);
        $this->assertEquals($testLabel, $this->entity->getLabel());
        $this->assertEquals($testLabel, (string)$this->entity);
    }
}
