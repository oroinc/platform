<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Resolver;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\SearchBundle\Resolver\EntityNameEntityTitleResolver;
use Oro\Bundle\SearchBundle\Resolver\EntityTitleResolverInterface;
use Oro\Bundle\SearchBundle\Tests\Unit\Stub\EntityStub;

class EntityNameEntityTitleResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnDecoratedNonEmptyTitle()
    {
        $resolver = $this->getResolver('John Doe', 'Mr. John Doe');
        $entity = new EntityStub(1);

        $this->assertEquals('John Doe', $resolver->resolve($entity));
    }

    public function testReturnEntityNameWhenEmptyDecoratedTitle()
    {
        $resolver = $this->getResolver('', 'Mr. John Doe');
        $entity = new EntityStub(1);

        $this->assertEquals('Mr. John Doe', $resolver->resolve($entity));
    }

    /**
     * @param  string $decoratedTitle
     * @param  string $entityName
     * @return EntityTitleResolverInterface
     */
    private function getResolver($decoratedTitle, $entityName)
    {
        $decoratedResolver = $this->getMock(EntityTitleResolverInterface::class);
        $decoratedResolver->expects($this->any())
            ->method('resolve')
            ->will($this->returnValue($decoratedTitle));

        $entityNameResolver = $this->getMockBuilder(EntityNameResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityNameResolver->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($entityName));

        return new EntityNameEntityTitleResolver($decoratedResolver, $entityNameResolver);
    }
}
