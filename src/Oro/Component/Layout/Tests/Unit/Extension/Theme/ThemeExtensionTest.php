<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;
use Oro\Component\Layout\ImportsAwareLayoutUpdateInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutItem;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\Loader\LayoutUpdateLoader;
use Oro\Component\Layout\Extension\Theme\PathProvider\ChainPathProvider;
use Oro\Component\Layout\Loader\Driver\DriverInterface;
use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\Extension\Theme\ThemeExtension;
use Oro\Component\Layout\Model\LayoutUpdateImport;
use Oro\Component\Layout\RawLayoutBuilder;
use Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\LayoutUpdateWithImports;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ThemeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ThemeExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ChainPathProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DriverInterface */
    protected $phpDriver;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DriverInterface */
    protected $yamlDriver;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DependencyInitializer */
    protected $dependencyInitializer;

    /** @var  ArrayCollection|LayoutUpdateImport[] */
    protected $importStorage;

    /** @var array */
    protected static $resources = [
        'oro-default' => [
            'resource1.yml',
            'resource2.xml',
            'resource3.php'
        ],
        'oro-gold' => [
            'resource-gold.yml',
            'index' => [
                'resource-update.yml'
            ]
        ],
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
        $this->provider = $this
            ->getMock('Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\StubContextAwarePathProvider');
        $this->yamlDriver = $this
            ->getMockBuilder('Oro\Component\Layout\Loader\Driver\DriverInterface')
            ->setMethods(['supports', 'load'])
            ->getMock();
        $this->phpDriver = $this
            ->getMockBuilder('Oro\Component\Layout\Loader\Driver\DriverInterface')
            ->setMethods(['supports', 'load'])
            ->getMock();

        $this->dependencyInitializer = $this
            ->getMockBuilder('Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer')
            ->disableOriginalConstructor()->getMock();

        $loader = new LayoutUpdateLoader();
        $loader->addDriver('yml', $this->yamlDriver);
        $loader->addDriver('php', $this->phpDriver);

        $this->importStorage = new ArrayCollection();

        $this->extension = new ThemeExtension(
            self::$resources,
            $loader,
            $this->dependencyInitializer,
            $this->provider,
            $this->importStorage
        );
    }

    public function testThemeWithoutUpdatesTheme()
    {
        $themeName = 'my-theme';
        $this->provider->expects($this->once())->method('getPaths')->willReturn([$themeName]);
        $result = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        $this->assertEquals([], $result);
    }

    public function testThemeYamlUpdateFound()
    {
        $themeName = 'oro-gold';
        $this->provider->expects($this->once())->method('getPaths')->willReturn([$themeName]);

        $updateMock = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');

        $this->yamlDriver->expects($this->once())->method('load')
            ->with('resource-gold.yml')
            ->willReturn($updateMock);

        $result = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        $this->assertContains($updateMock, $result);
    }

    public function testUpdatesFoundBasedOnMultiplePaths()
    {
        $themeName = 'oro-gold';
        $this->provider->expects($this->once())->method('getPaths')->willReturn([
            $themeName,
            $themeName.PathProviderInterface::DELIMITER.'index',
        ]);

        $updateMock = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');

        $this->yamlDriver->expects($this->at(0))->method('load')
            ->with('resource-gold.yml')
            ->willReturn($updateMock);

        $this->yamlDriver->expects($this->at(1))->method('load')
            ->with('resource-update.yml')
            ->willReturn($updateMock);

        $result = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        $this->assertContains($updateMock, $result);
    }

    public function testThemeUpdatesFoundWithOneSkipped()
    {
        $themeName = 'oro-default';
        $this->provider->expects($this->once())->method('getPaths')->willReturn([$themeName]);

        $updateMock = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');
        $update2Mock = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');

        $this->yamlDriver->expects($this->once())->method('load')
            ->with('resource1.yml')
            ->willReturn($updateMock);
        $this->phpDriver->expects($this->once())->method('load')
            ->with('resource3.php')
            ->willReturn($update2Mock);

        $result = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        $this->assertContains($updateMock, $result);
        $this->assertContains($update2Mock, $result);
    }

    public function testShouldPassDependenciesToUpdateInstance()
    {
        $themeName = 'oro-gold';
        $update = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');
        $this->provider->expects($this->once())->method('getPaths')->willReturn([$themeName]);

        $this->yamlDriver->expects($this->once())->method('load')->willReturn($update);

        $this->dependencyInitializer->expects($this->once())->method('initialize')->with($this->identicalTo($update));

        $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
    }

    public function testShouldPassContextInContextAwareProvider()
    {
        $themeName = 'my-theme';
        $this->provider->expects($this->once())->method('getPaths')->willReturn([$themeName]);

        $this->provider->expects($this->once())->method('setContext');

        $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
    }

    public function testThemeUpdatesWithImports()
    {
        $themeName = 'oro-import';
        $this->provider->expects($this->once())->method('getPaths')->willReturn([$themeName]);

        $layoutUpdate = new LayoutUpdateWithImports([
            [
                ImportsAwareLayoutUpdateInterface::ID_KEY => 'import_id',
                ImportsAwareLayoutUpdateInterface::ROOT_KEY => 'root_block_id',
                ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => 'import_namespace',
            ]
        ]);
        $importedLayoutUpdate = new LayoutUpdateWithImports(['second_level_import_id']);
        $secondLevelImportedLayoutUpdate = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');

        $this->yamlDriver->expects($this->exactly(3))
            ->method('load')
            ->will($this->returnValueMap([
                ['resource-gold.yml', $layoutUpdate],
                ['import-resource-gold.yml', $importedLayoutUpdate],
                ['second-level-import-resource-gold.yml', $secondLevelImportedLayoutUpdate],
            ]));

        $actualLayoutUpdates = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        $this->assertEquals(
            [$layoutUpdate, $importedLayoutUpdate, $secondLevelImportedLayoutUpdate],
            $actualLayoutUpdates
        );

        $this->assertCount(2, $this->importStorage);
        /** @var LayoutUpdateImport $import */
        $import = $this->importStorage->get('import-resource-gold.yml');
        $this->assertEquals($layoutUpdate->getImports()[0], $import->toArray());
        /** @var LayoutUpdateImport $secondLevelImport */
        $import = $this->importStorage->get('second-level-import-resource-gold.yml');
        $this->assertEquals([
            ImportsAwareLayoutUpdateInterface::ID_KEY => $importedLayoutUpdate->getImports()[0],
            ImportsAwareLayoutUpdateInterface::ROOT_KEY => null,
            ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => null,
        ], $import->toArray());
    }

    public function testThemeUpdatesWithImportsContainedMultipleUpdates()
    {
        $themeName = 'oro-import-multiple';
        $this->provider->expects($this->once())->method('getPaths')->willReturn([$themeName]);

        $layoutUpdate = new LayoutUpdateWithImports([
            [
                ImportsAwareLayoutUpdateInterface::ID_KEY => 'import_id',
                ImportsAwareLayoutUpdateInterface::ROOT_KEY => 'root_block_id',
                ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => 'import_namespace',
            ],
        ]);
        $importedLayoutUpdate = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');
        $secondLevelImportedLayoutUpdate = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');

        $this->yamlDriver->expects($this->exactly(3))
            ->method('load')
            ->will($this->returnValueMap([
                ['resource-gold.yml', $layoutUpdate],
                ['import-resource-gold.yml', $importedLayoutUpdate],
                ['second-import-resource-gold.yml', $secondLevelImportedLayoutUpdate],
            ]));

        $actualLayoutUpdates = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        $this->assertEquals(
            [$layoutUpdate, $importedLayoutUpdate, $secondLevelImportedLayoutUpdate],
            $actualLayoutUpdates
        );

        $this->assertCount(2, $this->importStorage);
        /** @var LayoutUpdateImport $import */
        $import = $this->importStorage->get('import-resource-gold.yml');
        $this->assertEquals($layoutUpdate->getImports()[0], $import->toArray());
        /** @var LayoutUpdateImport $secondLevelImport */
        $import = $this->importStorage->get('second-import-resource-gold.yml');
        $this->assertEquals($layoutUpdate->getImports()[0], $import->toArray());
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Imports statement should be an array, string given
     */
    public function testThemeUpdatesWithNonArrayImports()
    {
        $themeName = 'oro-import';
        $this->provider->expects($this->once())->method('getPaths')->willReturn([$themeName]);

        $layoutUpdate = new LayoutUpdateWithImports('string');

        $this->yamlDriver->expects($this->once())
            ->method('load')
            ->with('resource-gold.yml')
            ->willReturn($layoutUpdate);

        $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
    }

    public function testThemeUpdatesWithSameImport()
    {
        $themeName = 'oro-import';
        $this->provider->expects($this->once())->method('getPaths')->willReturn([$themeName]);

        $layoutUpdate = new LayoutUpdateWithImports([
            [
                ImportsAwareLayoutUpdateInterface::ID_KEY => 'import_id',
                ImportsAwareLayoutUpdateInterface::ROOT_KEY => 'root_block_id',
                ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => 'import_namespace',
            ],
            [
                ImportsAwareLayoutUpdateInterface::ID_KEY => 'import_id',
                ImportsAwareLayoutUpdateInterface::ROOT_KEY => 'second_root_block_id',
                ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => 'second_import_namespace',
            ],
        ]);
        $importedLayoutUpdate = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');
        $secondImportLayoutUpdate = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');

        $this->yamlDriver->expects($this->at(0))
            ->method('load')
            ->with('resource-gold.yml')
            ->willReturn($layoutUpdate);

        $this->yamlDriver->expects($this->at(1))
            ->method('load')
            ->with('import-resource-gold.yml')
            ->willReturn($importedLayoutUpdate);

        $this->yamlDriver->expects($this->at(2))
            ->method('load')
            ->with('import-resource-gold.yml')
            ->willReturn($secondImportLayoutUpdate);

        $actualLayoutUpdates = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        $this->assertEquals(
            [$layoutUpdate, $importedLayoutUpdate, $secondImportLayoutUpdate],
            $actualLayoutUpdates
        );

        $this->assertCount(1, $this->importStorage);
        /** @var LayoutUpdateImport $import */
        $import = $this->importStorage->get('import-resource-gold.yml');
        $this->assertEquals($layoutUpdate->getImports()[1], $import->toArray());
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
