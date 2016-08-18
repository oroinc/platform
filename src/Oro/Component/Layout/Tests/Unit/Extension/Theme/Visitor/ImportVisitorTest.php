<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Visitor;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ResourceProviderInterface;
use Oro\Component\Layout\Extension\Theme\ThemeExtension;
use Oro\Component\Layout\Extension\Theme\Visitor\ImportVisitor;
use Oro\Component\Layout\ImportsAwareLayoutUpdateInterface;
use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;
use Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\ImportedLayoutUpdate;
use Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\ImportedLayoutUpdateWithImports;
use Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\LayoutUpdateWithImports;

class ImportVisitorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ImportVisitor */
    protected $visitor;

    /** @var LayoutUpdateLoaderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $loader;

    /** @var DependencyInitializer|\PHPUnit_Framework_MockObject_MockObject */
    protected $dependencyInitializer;

    /** @var ResourceProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $resourceProvider;

    /** @var ThemeManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $themeManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->loader = $this->getMock(LayoutUpdateLoaderInterface::class);
        $this->dependencyInitializer = $this->getMock(DependencyInitializer::class, [], [], '', false);
        $this->resourceProvider = $this->getMock(ResourceProviderInterface::class);
        $this->themeManager = $this->getMock(ThemeManager::class, [], [], '', false);

        $this->visitor = new ImportVisitor(
            $this->loader,
            $this->dependencyInitializer,
            $this->resourceProvider,
            $this->themeManager
        );
    }

    public function testWalkUpdatesWithoutImports()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ContextInterface::class);

        $updates = ['root' => [$this->getMock(LayoutUpdateInterface::class)]];

        $this->visitor->walkUpdates($updates, $context);
        $this->assertEquals($updates, $updates);
    }

    public function testWalkUpdatesWithImports()
    {
        $themeName = 'oro-import';

        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('get')
            ->with(ThemeExtension::THEME_KEY)
            ->will($this->returnValue($themeName));

        $update = $this->getMock(LayoutUpdateWithImports::class);
        $update->expects($this->once())
            ->method('getImports')
            ->will($this->returnValue(
                [
                    [
                        ImportsAwareLayoutUpdateInterface::ID_KEY => 'import_id',
                        ImportsAwareLayoutUpdateInterface::ROOT_KEY => 'root_block_id',
                        ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => 'import_namespace'
                    ]
                ]
            ));

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

        $path = implode(
            DIRECTORY_SEPARATOR,
            [$theme->getName(), ImportVisitor::IMPORT_FOLDER, 'import_id']
        );

        $this->resourceProvider
            ->expects($this->once())
            ->method('findApplicableResources')
            ->with([$path])
            ->will($this->returnValue(['import/file']));

        /** @var ImportedLayoutUpdate|\PHPUnit_Framework_MockObject_MockObject $importUpdate */
        $importUpdate = $this->getMock(ImportedLayoutUpdate::class);

        $this->loader
            ->expects($this->once())
            ->method('load')
            ->with('import/file')
            ->will($this->returnValue($importUpdate));

        $this->dependencyInitializer
            ->expects($this->once())
            ->method('initialize')
            ->with($importUpdate);

        $updates = ['root' => [$update]];

        $expectedResult = $updates;
        $expectedResult['root'][] = $importUpdate;

        $this->visitor->walkUpdates($updates, $context);
        $this->assertEquals($expectedResult, $updates);
    }

    public function testWalkUpdatesWithImportsContainedMultipleUpdates()
    {
        $themeName = 'oro-import';

        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('get')
            ->with(ThemeExtension::THEME_KEY)
            ->will($this->returnValue($themeName));

        $update = $this->getMock(LayoutUpdateWithImports::class);
        $update->expects($this->once())
            ->method('getImports')
            ->will($this->returnValue(
                ['import_id']
            ));

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

        $path = implode(
            DIRECTORY_SEPARATOR,
            [$theme->getName(), ImportVisitor::IMPORT_FOLDER, 'import_id']
        );

        $this->resourceProvider
            ->expects($this->once())
            ->method('findApplicableResources')
            ->with([$path])
            ->will($this->returnValue(['import/file']));

        /** @var ImportedLayoutUpdateWithImports|\PHPUnit_Framework_MockObject_MockObject $importUpdate */
        $importUpdate = $this->getMock(ImportedLayoutUpdateWithImports::class);
        $importUpdate->expects($this->once())
            ->method('getImports')
            ->will($this->returnValue([]));

        $this->loader
            ->expects($this->once())
            ->method('load')
            ->with('import/file')
            ->will($this->returnValue($importUpdate));

        $this->dependencyInitializer
            ->expects($this->once())
            ->method('initialize')
            ->with($importUpdate);

        $updates = ['root' => [$update]];

        $expectedResult = $updates;
        $expectedResult['root'][] = $importUpdate;

        $this->visitor->walkUpdates($updates, $context);
        $this->assertEquals($expectedResult, $updates);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Imports statement should be an array, string given
     */
    public function testWalkUpdatesWithNonArrayImports()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ContextInterface::class);

        $update = $this->getMock(LayoutUpdateWithImports::class);
        $update->expects($this->once())
            ->method('getImports')
            ->will($this->returnValue('string'));

        $updates = ['root' => [$update]];

        $this->visitor->walkUpdates($updates, $context);
    }
}
