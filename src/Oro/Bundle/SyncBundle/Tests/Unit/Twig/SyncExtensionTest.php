<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Twig;

use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;
use Oro\Bundle\SyncBundle\Twig\SyncExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SyncExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private ConnectionChecker&MockObject $connectionChecker;
    private TagGeneratorInterface&MockObject $tagGenerator;
    private SyncExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->connectionChecker = $this->createMock(ConnectionChecker::class);
        $this->tagGenerator = $this->createMock(TagGeneratorInterface::class);

        $container = self::getContainerBuilder()
            ->add(ConnectionChecker::class, $this->connectionChecker)
            ->add(TagGeneratorInterface::class, $this->tagGenerator)
            ->getContainer($this);

        $this->extension = new SyncExtension($container);
    }

    public function testGenerate(): void
    {
        $data = 'string';
        $tags = ['string_tag'];

        $this->tagGenerator->expects($this->once())
            ->method('generate')
            ->with($this->equalTo($data), $this->equalTo(false))
            ->willReturn($tags);

        $this->assertSame(
            $tags,
            self::callTwigFunction($this->extension, 'oro_sync_get_content_tags', [$data])
        );
    }

    public function testCheckWsConnected(): void
    {
        $this->connectionChecker->expects($this->once())
            ->method('checkConnection')
            ->willReturn(true);

        $this->assertTrue(self::callTwigFunction($this->extension, 'check_ws', []));
    }

    public function testWsConnectedFail(): void
    {
        $this->connectionChecker->expects($this->once())
            ->method('checkConnection')
            ->willReturn(false);

        $this->assertFalse(self::callTwigFunction($this->extension, 'check_ws', []));
    }
}
