<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Twig;

use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;
use Oro\Bundle\SyncBundle\Twig\OroSyncExtension;
use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class OroSyncExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $topicPublisher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $tagGenerator;

    /** @var OroSyncExtension */
    protected $extension;

    protected function setUp()
    {
        $this->topicPublisher = $this->createMock(TopicPublisher::class);
        $this->tagGenerator = $this->createMock(TagGeneratorInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_wamp.publisher', $this->topicPublisher)
            ->add('oro_sync.content.tag_generator_chain', $this->tagGenerator)
            ->getContainer($this);

        $this->extension = new OroSyncExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals('sync_extension', $this->extension->getName());
    }

    public function testGenerate()
    {
        $data = 'string';
        $tags = ['string_tag'];

        $this->tagGenerator->expects($this->once())->method('generate')
            ->with($this->equalTo($data), $this->equalTo(false))
            ->will($this->returnValue($tags));

        $this->assertSame(
            $tags,
            self::callTwigFunction($this->extension, 'oro_sync_get_content_tags', [$data])
        );
    }
}
