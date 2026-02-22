<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Twig;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationRegistryInterface;
use Oro\Bundle\ImportExportBundle\Twig\ImportExportExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImportExportExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private ImportExportConfigurationRegistryInterface&MockObject $configurationRegistry;
    private ImportExportExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->configurationRegistry = $this->createMock(ImportExportConfigurationRegistryInterface::class);

        $container = self::getContainerBuilder()
            ->add(ImportExportConfigurationRegistryInterface::class, $this->configurationRegistry)
            ->getContainer($this);

        $this->extension = new ImportExportExtension($container);
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
            self::callTwigFunction($this->extension, 'get_import_export_configuration', [$alias])
        );
    }

    public function testGetBasenameFilter(): void
    {
        self::assertSame('3', self::callTwigFilter($this->extension, 'basename', ['1\\2\\3']));
    }
}
