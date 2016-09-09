<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Resolver;

use Oro\Bundle\SearchBundle\Resolver\EntityTitleResolverInterface;
use Oro\Bundle\SearchBundle\Resolver\EntityToStringTitleResolver;
use Oro\Bundle\SearchBundle\Tests\Unit\Stub\EntityStub;

class EntityToStringTitleResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnDecoratedNonEmptyTitle()
    {
        $resolver = $this->getResolver('John Doe');
        $entity = new EntityStub(1, 'John');

        $this->assertEquals('John Doe', $resolver->resolve($entity));
    }

    public function testReturnEntityToString()
    {
        $resolver = $this->getResolver('');
        $entity = new EntityStub(1, 'John');

        $this->assertEquals('John', $resolver->resolve($entity));
    }

    /**
     * @param  string $decoratedTitle
     * @return EntityTitleResolverInterface
     */
    private function getResolver($decoratedTitle)
    {
        $decoratedResolver = $this->getMock(EntityTitleResolverInterface::class);
        $decoratedResolver->expects($this->any())
            ->method('resolve')
            ->will($this->returnValue($decoratedTitle));

        return new EntityToStringTitleResolver($decoratedResolver);
    }
}
