<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Twig;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationRegistryInterface;
use Oro\Bundle\ImportExportBundle\Twig\GetImportExportConfigurationExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Twig\TwigFunction;

class GetImportExportConfigurationExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ImportExportConfigurationRegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationRegistry;

    /** @var GetImportExportConfigurationExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->configurationRegistry = $this->createMock(ImportExportConfigurationRegistryInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_importexport.configuration.registry', $this->configurationRegistry)
            ->getContainer($this);

        $this->extension = new GetImportExportConfigurationExtension($container);
    }

    public function testGetFunctions()
    {
        $expected = [
            new TwigFunction('get_import_export_configuration', [$this->extension, 'getConfiguration'])
        ];

        self::assertEquals($expected, $this->extension->getFunctions());
    }

    public function testGetConfiguration()
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
