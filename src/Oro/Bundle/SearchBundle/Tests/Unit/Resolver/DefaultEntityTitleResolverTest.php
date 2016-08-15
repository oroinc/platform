<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Resolver;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\SearchBundle\Resolver\DefaultEntityTitleResolver;
use Oro\Bundle\SearchBundle\Resolver\EntityTitleResolverInterface;
use Oro\Bundle\SearchBundle\Tests\Unit\Stub\EntityStub;

class DefaultEntityTitleResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnDecoratedNonEmptyTitle()
    {
        $resolver = $this->getResolver('John Doe');
        $entity = new EntityStub(1);

        $this->assertEquals('John Doe', $resolver->resolve($entity));
    }

    public function testReturnDefaultTitle()
    {
        $resolver = $this->getResolver(null, 'Default Title');
        $entity = new EntityStub(1);

        $this->assertEquals('Default Title', $resolver->resolve($entity));
    }

    /**
     * @param  string|null $decoratedTitle
     * @param  string|null $translation
     * @return EntityTitleResolverInterface
     */
    private function getResolver($decoratedTitle, $translation = null)
    {
        $decoratedResolver = $this->getMock(EntityTitleResolverInterface::class);
        $decoratedResolver->expects($this->any())
            ->method('resolve')
            ->will($this->returnValue($decoratedTitle));

        $translator = $this->getMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnValue($translation));

        return new DefaultEntityTitleResolver($decoratedResolver, $translator);
    }
}
