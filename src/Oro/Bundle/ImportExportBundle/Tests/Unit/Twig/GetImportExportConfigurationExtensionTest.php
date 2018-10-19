<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Twig;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationRegistryInterface;
use Oro\Bundle\ImportExportBundle\Twig\GetImportExportConfigurationExtension;
use PHPUnit\Framework\TestCase;

class GetImportExportConfigurationExtensionTest extends TestCase
{
    /**
     * @var ImportExportConfigurationRegistryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configurationRegistry;

    /**
     * @var GetImportExportConfigurationExtension
     */
    private $extension;

    protected function setUp()
    {
        $this->configurationRegistry = $this->createMock(ImportExportConfigurationRegistryInterface::class);

        $this->extension = new GetImportExportConfigurationExtension($this->configurationRegistry);
    }

    public function testGetFunctions()
    {
        $expected = [
            new \Twig_SimpleFunction('get_import_export_configuration', [$this->extension, 'getConfiguration'])
        ];

        static::assertEquals($expected, $this->extension->getFunctions());
    }

    public function testGetConfiguration()
    {
        $alias = 'test';

        $expectedResult = [$this->createMock(ImportExportConfigurationInterface::class)];

        $this->configurationRegistry
            ->expects(static::once())
            ->method('getConfigurations')
            ->with($alias)
            ->willReturn($expectedResult);

        static::assertSame($expectedResult, $this->extension->getConfiguration($alias));
    }
}
