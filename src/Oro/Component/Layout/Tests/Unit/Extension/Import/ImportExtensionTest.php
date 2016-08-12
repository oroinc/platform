<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme;

use Oro\Component\Layout\Extension\Import\ImportExtension;
use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\Extension\Theme\PathProvider\ChainPathProvider;
use Oro\Component\Layout\ImportsAwareLayoutUpdateInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutItem;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\Loader\Driver\DriverInterface;
use Oro\Component\Layout\Loader\LayoutUpdateLoader;
use Oro\Component\Layout\Model\LayoutUpdateImport;
use Oro\Component\Layout\RawLayoutBuilder;
use Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\ImportedLayoutUpdate;
use Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\ImportedLayoutUpdateWithImports;
use Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\LayoutUpdateWithImports;
use Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\StubContextAwarePathProvider;

class ImportExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ImportExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ChainPathProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DriverInterface */
    protected $phpDriver;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DriverInterface */
    protected $yamlDriver;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DependencyInitializer */
    protected $dependencyInitializer;

    /** @var  \PHPUnit_Framework_MockObject_MockObject|ThemeManager */
    protected $themeManager;

    /** @var array */
    protected static $resources = [
        'oro-import' => [
            'resource-gold.yml',
            'imports' => [
                'import_id' => [
                    'import-resource-gold.yml'
                ],
                'second_level_import_id' => [
                    'second-level-import-resource-gold.yml'
                ]
            ],
        ],
        'oro-import-multiple' => [
            'resource-gold.yml',
            'imports' => [
                'import_id' => [
                    'import-resource-gold.yml',
                    'second-import-resource-gold.yml',
                ],
            ],
        ],
    ];

    protected function setUp()
    {
        $this->provider = $this->getMock(StubContextAwarePathProvider::class);
        $this->yamlDriver = $this->getMock(DriverInterface::class, ['supports', 'load']);
        $this->phpDriver = $this->getMock(DriverInterface::class, ['supports', 'load']);
        $this->dependencyInitializer = $this->getMock(DependencyInitializer::class, [], [], '', false);
        $this->themeManager = $this->getMock(ThemeManager::class, [], [], '', false);

        $loader = new LayoutUpdateLoader();
        $loader->addDriver('yml', $this->yamlDriver);
        $loader->addDriver('php', $this->phpDriver);

        $this->extension = new ImportExtension(
            self::$resources,
            $loader,
            $this->dependencyInitializer,
            $this->provider,
            $this->themeManager
        );
    }

    public function testThemeUpdatesWithImports()
    {
        $themeName = 'oro-import';
        $this->provider
            ->expects($this->once())
            ->method('getPaths')
            ->willReturn([$themeName]);

        $layoutUpdate = $this->getMock(LayoutUpdateWithImports::class);
        $layoutUpdate->expects($this->once())
            ->method('getImports')
            ->willReturn(
                [
                    [
                        ImportsAwareLayoutUpdateInterface::ID_KEY => 'import_id',
                        ImportsAwareLayoutUpdateInterface::ROOT_KEY => 'root_block_id',
                        ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => 'import_namespace'
                    ]
                ]
            );

        $importedLayoutUpdateWithImports = $this->getMock(ImportedLayoutUpdateWithImports::class);
        $importedLayoutUpdateWithImports->expects($this->once())
            ->method('getImports')
            ->willReturn(['second_level_import_id']);
        $importedLayoutUpdateWithImports->expects($this->once())
            ->method('setImport')
            ->with(new LayoutUpdateImport('import_id', 'root_block_id', 'import_namespace'));

        $secondLevelImportedLayoutUpdate = $this->getMock(ImportedLayoutUpdate::class);
        $secondLevelImportedLayoutUpdate->expects($this->once())
            ->method('setImport')
            ->with(new LayoutUpdateImport('second_level_import_id', null, null));

        $this->yamlDriver
            ->expects($this->exactly(3))
            ->method('load')
            ->will(
                $this->returnValueMap(
                    [
                        ['resource-gold.yml', $layoutUpdate],
                        ['import-resource-gold.yml', $importedLayoutUpdateWithImports],
                        ['second-level-import-resource-gold.yml', $secondLevelImportedLayoutUpdate],
                    ]
                )
            );

        /** @var Theme|\PHPUnit_Framework_MockObject_MockObject $theme */
        $theme = $this->getMock(Theme::class, [], [$themeName]);
        $theme->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($themeName));

        $this->themeManager
            ->expects($this->any())
            ->method('getTheme')
            ->with($themeName)
            ->will($this->returnValue($theme));

        $actualLayoutUpdates = $this->extension
            ->getLayoutUpdates($this->getLayoutItem('root', $themeName));

        $this->assertEquals(
            [$importedLayoutUpdateWithImports, $secondLevelImportedLayoutUpdate],
            $actualLayoutUpdates
        );
    }
    public function testThemeUpdatesWithImportsContainedMultipleUpdates()
    {
        $themeName = 'oro-import-multiple';
        $this->provider
            ->expects($this->once())
            ->method('getPaths')
            ->willReturn([$themeName]);

        $layoutUpdate = $this->getMock(LayoutUpdateWithImports::class);
        $layoutUpdate->expects($this->once())
            ->method('getImports')
            ->willReturn(
                [
                    [
                        ImportsAwareLayoutUpdateInterface::ID_KEY => 'import_id',
                        ImportsAwareLayoutUpdateInterface::ROOT_KEY => 'root_block_id',
                        ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => 'import_namespace'
                    ]
                ]
            );

        $import = new LayoutUpdateImport('import_id', 'root_block_id', 'import_namespace');

        $importedLayoutUpdate = $this->getMock(ImportedLayoutUpdate::class);
        $importedLayoutUpdate->expects($this->once())
            ->method('setImport')
            ->with($import);

        $secondLevelImportedLayoutUpdate = $this->getMock(ImportedLayoutUpdate::class);
        $secondLevelImportedLayoutUpdate->expects($this->once())
            ->method('setImport')
            ->with($import);

        $this->yamlDriver
            ->expects($this->exactly(3))
            ->method('load')
            ->will(
                $this->returnValueMap(
                    [
                        ['resource-gold.yml', $layoutUpdate],
                        ['import-resource-gold.yml', $importedLayoutUpdate],
                        ['second-import-resource-gold.yml', $secondLevelImportedLayoutUpdate],
                    ]
                )
            );

        /** @var Theme|\PHPUnit_Framework_MockObject_MockObject $theme */
        $theme = $this->getMock(Theme::class, [], [$themeName]);
        $theme->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($themeName));

        $this->themeManager
            ->expects($this->any())
            ->method('getTheme')
            ->with($themeName)
            ->will($this->returnValue($theme));

        $actualLayoutUpdates = $this->extension
            ->getLayoutUpdates($this->getLayoutItem('root', $themeName));

        $this->assertEquals(
            [$importedLayoutUpdate, $secondLevelImportedLayoutUpdate],
            $actualLayoutUpdates
        );
    }
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Imports statement should be an array, string given
     */
    public function testThemeUpdatesWithNonArrayImports()
    {
        $themeName = 'oro-import';
        $this->provider
            ->expects($this->once())
            ->method('getPaths')
            ->willReturn([$themeName]);

        $layoutUpdate = $this->getMock(LayoutUpdateWithImports::class);
        $layoutUpdate->expects($this->once())
            ->method('getImports')
            ->willReturn('test string');

        $this->yamlDriver
            ->expects($this->once())
            ->method('load')
            ->with('resource-gold.yml')
            ->willReturn($layoutUpdate);

        $this->extension
            ->getLayoutUpdates($this->getLayoutItem('root', $themeName));
    }
    public function testThemeUpdatesWithSameImport()
    {
        $themeName = 'oro-import';
        $this->provider
            ->expects($this->once())
            ->method('getPaths')
            ->willReturn([$themeName]);

        $layoutUpdate = $this->getMock(LayoutUpdateWithImports::class);
        $layoutUpdate->expects($this->once())
            ->method('getImports')
            ->willReturn(
                [
                    [
                        ImportsAwareLayoutUpdateInterface::ID_KEY => 'import_id',
                        ImportsAwareLayoutUpdateInterface::ROOT_KEY => 'root_block_id',
                        ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => 'import_namespace',
                    ],
                    [
                        ImportsAwareLayoutUpdateInterface::ID_KEY => 'import_id',
                        ImportsAwareLayoutUpdateInterface::ROOT_KEY => 'second_root_block_id',
                        ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => 'second_import_namespace',
                    ]
                ]
            );

        $importedLayoutUpdate = $this->getMock(ImportedLayoutUpdate::class);
        $importedLayoutUpdate->expects($this->once())
            ->method('setImport')
            ->with(new LayoutUpdateImport('import_id', 'root_block_id', 'import_namespace'));

        $secondImportLayoutUpdate = $this->getMock(ImportedLayoutUpdate::class);
        $secondImportLayoutUpdate->expects($this->once())
            ->method('setImport')
            ->with(new LayoutUpdateImport('import_id', 'second_root_block_id', 'second_import_namespace'));

        $this->yamlDriver
            ->expects($this->at(0))
            ->method('load')
            ->with('resource-gold.yml')
            ->willReturn($layoutUpdate);
        $this->yamlDriver
            ->expects($this->at(1))
            ->method('load')
            ->with('import-resource-gold.yml')
            ->willReturn($importedLayoutUpdate);
        $this->yamlDriver
            ->expects($this->at(2))
            ->method('load')
            ->with('import-resource-gold.yml')
            ->willReturn($secondImportLayoutUpdate);

        /** @var Theme|\PHPUnit_Framework_MockObject_MockObject $theme */
        $theme = $this->getMock(Theme::class, [], [$themeName]);
        $theme->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($themeName));

        $this->themeManager
            ->expects($this->any())
            ->method('getTheme')
            ->with($themeName)
            ->will($this->returnValue($theme));

        $actualLayoutUpdates = $this->extension
            ->getLayoutUpdates($this->getLayoutItem('root', $themeName));

        $this->assertEquals(
            [$importedLayoutUpdate, $secondImportLayoutUpdate],
            $actualLayoutUpdates
        );
    }

    /**
     * @param string $id
     * @param null|string $theme
     *
     * @return LayoutItemInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLayoutItem($id, $theme = null)
    {
        $context = new LayoutContext();
        $context->set('theme', $theme);

        $layoutItem = (new LayoutItem(new RawLayoutBuilder(), $context));
        $layoutItem->initialize($id);

        return $layoutItem;
    }
}
