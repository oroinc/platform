<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Twig;

use Oro\Bundle\PlatformBundle\Composer\VersionHelper;
use Oro\Bundle\PlatformBundle\Twig\PlatformExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class PlatformExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $versionHelper;

    /** @var PlatformExtension */
    protected $extension;

    protected function setUp(): void
    {
        $this->versionHelper = $this->getMockBuilder(VersionHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_platform.composer.version_helper', $this->versionHelper)
            ->getContainer($this);

        $this->extension = new PlatformExtension($container);
    }

    /**
     * @return string
     */
    public function testGetVersion()
    {
        $undefinedVersion = 'N/A';

        $this->versionHelper->expects($this->once())
            ->method('getVersion')
            ->willReturn($undefinedVersion);

        $this->assertEquals(
            $undefinedVersion,
            self::callTwigFunction($this->extension, 'oro_version', [])
        );
    }

    /**
     * @return string The extension name
     */
    public function testGetName()
    {
        $this->assertEquals(PlatformExtension::EXTENSION_NAME, $this->extension->getName());
    }
}
