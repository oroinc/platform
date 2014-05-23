<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Twig;

use Oro\Bundle\PlatformBundle\Composer\VersionHelper;
use Oro\Bundle\PlatformBundle\Twig\PlatformExtension;

class PlatformExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PlatformExtension
     */
    protected $extension;

    protected function setUp()
    {
        $helper = $this
            ->getMockBuilder('Oro\Bundle\PlatformBundle\Composer\VersionHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $helper
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue(VersionHelper::UNDEFINED_VERSION));

        $this->extension = new PlatformExtension($helper);
    }

    public function testGetFunctions()
    {
        $this->assertArrayHasKey('oro_version', $this->extension->getFunctions());
    }

    /**
     * @return string
     */
    public function testGetVersion()
    {
        $this->assertEquals(VersionHelper::UNDEFINED_VERSION, $this->extension->getVersion());
    }

    /**
     * @return string The extension name
     */
    public function testGetName()
    {
        $this->assertEquals(PlatformExtension::EXTENSION_NAME, $this->extension->getName());
    }
}
