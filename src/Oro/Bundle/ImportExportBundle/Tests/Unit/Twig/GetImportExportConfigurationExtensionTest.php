<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Twig;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationRegistryInterface;
use Oro\Bundle\ImportExportBundle\Twig\GetImportExportConfigurationExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class GetImportExportConfigurationExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private ImportExportConfigurationRegistryInterface&MockObject $configurationRegistry;
    private GetImportExportConfigurationExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->configurationRegistry = $this->createMock(ImportExportConfigurationRegistryInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_importexport.configuration.registry', $this->configurationRegistry)
            ->getContainer($this);

        $this->extension = new GetImportExportConfigurationExtension($container);
    }

    public function testGetFunctions(): void
    {
        $expected = [
            new TwigFunction('get_import_export_configuration', [$this->extension, 'getConfiguration'])
        ];

        self::assertEquals($expected, $this->extension->getFunctions());
    }

    public function testGetConfiguration(): void
    {
        $alias = 'test';

        $expectedResult = [$this->createMock(ImportExportConfigurationInterface::class)];

        $this->configurationRegistry->expects(self::once())
            ->method('getConfigurations')
            ->with($alias)
            ->willReturn($expectedResult);

        self::assertSame(
            $expectedResult,
            $this->callTwigFunction($this->extension, 'get_import_export_configuration', [$alias])
        );
    }
}
