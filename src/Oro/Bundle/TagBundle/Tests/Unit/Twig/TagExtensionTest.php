<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Twig;

use Oro\Bundle\TagBundle\Entity\Taggable;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\TagBundle\Twig\TagExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TagExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private TagManager&MockObject $manager;
    private TaggableHelper&MockObject $helper;
    private TagExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->manager = $this->createMock(TagManager::class);
        $this->helper = $this->createMock(TaggableHelper::class);

        $container = self::getContainerBuilder()
            ->add('oro_tag.tag.manager', $this->manager)
            ->add('oro_tag.helper.taggable_helper', $this->helper)
            ->getContainer($this);

        $this->extension = new TagExtension($container);
    }

    public function testGetList(): void
    {
        $entity = $this->createMock(Taggable::class);
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
