<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Twig;

use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\TagBundle\Twig\TagExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class TagExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var TagExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TagManager */
    protected $manager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TaggableHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->manager = $this->getMockBuilder(TagManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->helper = $this->getMockBuilder(TaggableHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_tag.tag.manager', $this->manager)
            ->add('oro_tag.helper.taggable_helper', $this->helper)
            ->getContainer($this);

        $this->extension = new TagExtension($container);
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

    public function testGetList()
    {
        $entity = $this->createMock('Oro\Bundle\TagBundle\Entity\Taggable');
        $expected = ['test tag'];

        $this->manager->expects($this->once())
            ->method('getPreparedArray')
            ->with(self::identicalTo($entity))
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'oro_tag_get_list', [$entity])
        );
    }
}
