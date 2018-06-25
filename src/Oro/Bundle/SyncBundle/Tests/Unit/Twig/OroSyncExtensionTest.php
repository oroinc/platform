<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Twig;

use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;
use Oro\Bundle\SyncBundle\Twig\OroSyncExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class OroSyncExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ConnectionChecker|\PHPUnit\Framework\MockObject\MockObject */
    protected $connectionChecker;

    /** @var TagGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tagGenerator;

    /** @var OroSyncExtension */
    protected $extension;

    protected function setUp()
    {
        $this->connectionChecker = $this->createMock(ConnectionChecker::class);
        $this->tagGenerator = $this->createMock(TagGeneratorInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_sync.client.connection_checker', $this->connectionChecker)
            ->add('oro_sync.content.tag_generator', $this->tagGenerator)
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

    public function testCheckWsConnected()
    {
        $this->connectionChecker->expects($this->once())
            ->method('checkConnection')
            ->willReturn(true);

        $this->assertTrue(self::callTwigFunction($this->extension, 'check_ws', []));
    }

    public function testWsConnectedFail()
    {
        $this->connectionChecker->expects($this->once())
            ->method('checkConnection')
            ->willReturn(false);

        $this->assertFalse(self::callTwigFunction($this->extension, 'check_ws', []));
    }
}
