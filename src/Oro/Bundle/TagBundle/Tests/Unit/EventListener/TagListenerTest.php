<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\EventListener;

use Oro\Bundle\TagBundle\EventListener\TagListener;
use Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable;
use Oro\Bundle\TagBundle\Entity\Taggable as TaggableInterface;

class TagListenerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ID = 1;

    /**
     * @var TagListener
     */
    private $listener;

    /**
     * @var TaggableInterface
     */
    private $resource;

    protected function setUp()
    {
        $this->resource = new Taggable(['id' => self::TEST_ID]);
    }

    protected function tearDown()
    {
        unset($this->listener);
        unset($this->resource);
    }

    /**
     * Test pre-remove doctrine listener
     */
    public function testPreRemove()
    {
        $helper = $this->getMockBuilder('Oro\Bundle\TagBundle\Helper\TaggableHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $helper->expects($this->once())
            ->method('isTaggable')
            ->with($this->resource)
            ->willReturn(true);

        $manager = $this->getMockBuilder('Oro\Bundle\TagBundle\Entity\TagManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->once())
            ->method('deleteTagging')
            ->with($this->resource, []);

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('oro_tag.tag.manager'))
            ->will($this->returnValue($manager));

        $this->listener = new TagListener($helper);
        $this->listener->setContainer($container);

        $args = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $args->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($this->resource));

        $this->listener->preRemove($args);
    }
}
