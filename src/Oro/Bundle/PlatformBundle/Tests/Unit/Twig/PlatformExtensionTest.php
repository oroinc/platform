<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Twig;

use Oro\Bundle\PlatformBundle\Composer\VersionHelper;
use Oro\Bundle\PlatformBundle\Twig\PlatformExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlatformExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private VersionHelper&MockObject $versionHelper;
    private PlatformExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->versionHelper = $this->createMock(VersionHelper::class);

        $container = self::getContainerBuilder()
            ->add(VersionHelper::class, $this->versionHelper)
            ->getContainer($this);

        $this->extension = new PlatformExtension($container);
    }

    public function testGetVersion(): void
    {
        $version = '1.2';

        $this->versionHelper->expects(self::once())
            ->method('getVersion')
            ->willReturn($version);

        self::assertEquals(
            $version,
            self::callTwigFunction($this->extension, 'oro_version', [])
        );
    }
}
