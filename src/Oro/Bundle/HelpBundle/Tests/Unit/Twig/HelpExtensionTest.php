<?php

namespace Oro\Bundle\HelpBundle\Tests\Unit\Twig;

use Oro\Bundle\HelpBundle\Provider\HelpLinkProvider;
use Oro\Bundle\HelpBundle\Twig\HelpExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class HelpExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $linkProvider;

    /** @var HelpExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->linkProvider = $this->getMockBuilder(HelpLinkProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_help.help_link_provider', $this->linkProvider)
            ->getContainer($this);

        $this->extension = new HelpExtension($container);
    }

    public function testGetHelpLinkUrl()
    {
        $expects = 'http://server.com/help/list';

        $this->linkProvider
            ->expects($this->once())
            ->method('getHelpLinkUrl')
            ->will($this->returnValue($expects));

        $this->assertEquals(
            $expects,
            self::callTwigFunction($this->extension, 'get_help_link', [])
        );
    }
}
