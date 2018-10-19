<?php

namespace Oro\Bundle\HelpBundle\Tests\Unit\Twig;

use Oro\Bundle\HelpBundle\Model\HelpLinkProvider;
use Oro\Bundle\HelpBundle\Twig\HelpExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class HelpExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $linkProvider;

    /** @var HelpExtension */
    protected $extension;

    protected function setUp()
    {
        $this->linkProvider = $this->getMockBuilder(HelpLinkProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_help.model.help_link_provider', $this->linkProvider)
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

    public function testGetName()
    {
        $this->assertEquals(HelpExtension::NAME, $this->extension->getName());
    }
}
