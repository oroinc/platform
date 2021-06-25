<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Provider;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\SegmentBundle\Provider\EntityNameProvider;

class EntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityNameProvider */
    private $provider;

    /** @var AbstractQueryDesigner|\PHPUnit\Framework\MockObject\MockObject */
    private $entity;

    protected function setUp(): void
    {
        $this->entity = $this->createMock(AbstractQueryDesigner::class);

        $this->provider = new EntityNameProvider();
    }

    public function testProvider()
    {
        $this->assertFalse($this->provider->getEntityName());

        $entityName = 'Acme\Entity\Test\Entity';
        $this->entity->expects($this->once())
            ->method('getEntity')
            ->willReturn($entityName);

        $this->provider->setCurrentItem($this->entity);
        $this->assertEquals($entityName, $this->provider->getEntityName());
    }
}
