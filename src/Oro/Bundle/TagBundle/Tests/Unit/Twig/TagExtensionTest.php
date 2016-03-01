<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Twig;

use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\TagBundle\Twig\TagExtension;

class TagExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var TagExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TagManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TaggableHelper */
    protected $helper;

    /**
     * Set up test environment
     */
    protected function setUp()
    {
        $this->manager = $this->getMockBuilder('Oro\Bundle\TagBundle\Entity\TagManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = $this->getMockBuilder('Oro\Bundle\TagBundle\Helper\TaggableHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new TagExtension($this->manager, $this->helper);
    }

    protected function tearDown()
    {
        unset($this->manager);
        unset($this->extension);
    }

    public function testName()
    {
        $this->assertEquals('oro_tag', $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $functionsKeys = array_keys($this->extension->getFunctions());
        $this->assertEquals(['oro_tag_get_list', 'oro_is_taggable'], $functionsKeys);
    }

    public function testGetList()
    {
        $entity = $this->getMock('Oro\Bundle\TagBundle\Entity\Taggable');

        $this->manager->expects($this->once())
            ->method('getPreparedArray')
            ->with($entity);

        $this->extension->getList($entity);
    }
}
