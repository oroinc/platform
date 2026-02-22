<?php

namespace Oro\Bundle\HelpBundle\Tests\Unit\Twig;

use Oro\Bundle\HelpBundle\Provider\HelpLinkProvider;
use Oro\Bundle\HelpBundle\Twig\HelpExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HelpExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private HelpLinkProvider&MockObject $linkProvider;
    private HelpExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->linkProvider = $this->createMock(HelpLinkProvider::class);

        $container = self::getContainerBuilder()
            ->add(HelpLinkProvider::class, $this->linkProvider)
            ->getContainer($this);

        $this->extension = new HelpExtension($container);
    }

    public function testGetHelpLinkUrl(): void
    {
        $expects = 'http://server.com/help/list';

        $this->linkProvider->expects(self::once())
            ->method('getHelpLinkUrl')
            ->willReturn($expects);

        self::assertEquals(
            $expects,
            self::callTwigFunction($this->extension, 'get_help_link', [])
        );
    }
}
