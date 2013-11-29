<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Transport;

class TransportTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ID = 123;

    /** @var Transport|\PHPUnit_Framework_MockObject_MockObject */
    protected $entity;

    public function setUp()
    {
        $this->entity = $this->getMockForAbstractClass('Oro\Bundle\IntegrationBundle\Entity\Transport');
    }

    public function tearDown()
    {
        unset($this->entity);
    }

    public function testEntityMethods()
    {
        $this->assertNull($this->entity->getId());
    }
}
