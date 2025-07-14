<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Provider;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\SegmentBundle\Provider\EntityNameProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityNameProviderTest extends TestCase
{
    private EntityNameProvider $provider;
    private AbstractQueryDesigner&MockObject $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->entity = $this->createMock(AbstractQueryDesigner::class);

        $this->provider = new EntityNameProvider();
    }

    public function testProvider(): void
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
