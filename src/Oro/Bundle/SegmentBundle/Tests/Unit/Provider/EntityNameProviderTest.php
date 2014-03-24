<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Provider;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\SegmentBundle\Provider\EntityNameProvider;

class EntityNameProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityNameProvider */
    protected $provider;

    /** @var AbstractQueryDesigner|\PHPUnit_Framework_MockObject_MockObject */
    protected $entity;

    public function setUp()
    {
        $this->entity = $this->getMockBuilder('Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner')
            ->setMethods(['getEntity'])
            ->getMockForAbstractClass();

        $this->provider = new EntityNameProvider();
    }

    public function testProvider()
    {
        $this->assertFalse($this->provider->getEntityName());

        $entityName = 'Acme\Entity\Test\Entity';
        $this->entity->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entityName));

        $this->provider->setCurrentItem($this->entity);
        $this->assertEquals($entityName, $this->provider->getEntityName());
    }
}
