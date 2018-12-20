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
use Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\NotApplicableImportAwareLayoutUpdateStub;

class ImportVisitorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImportVisitor */
    protected $visitor;

    /** @var LayoutUpdateLoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $loader;

    /** @var DependencyInitializer|\PHPUnit\Framework\MockObject\MockObject */
    protected $dependencyInitializer;

    /** @var ResourceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $resourceProvider;

    /** @var ThemeManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $themeManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->loader = $this->createMock(LayoutUpdateLoaderInterface::class);
        $this->dependencyInitializer = $this->createMock(DependencyInitializer::class);
        $this->resourceProvider = $this->createMock(ResourceProviderInterface::class);
        $this->themeManager = $this->createMock(ThemeManager::class);

        $this->visitor = new ImportVisitor(
            $this->loader,
            $this->dependencyInitializer,
            $this->resourceProvider,
            $this->themeManager
        );
    }

    public function testWalkUpdatesWithoutImports()
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);

        $updates = ['root' => [$this->createMock(LayoutUpdateInterface::class)]];

        $this->visitor->walkUpdates($updates, $context);
        $this->assertEquals($updates, $updates);
    }

    public function testWalkUpdatesWithImports()
    {
        $themeName = 'oro-import';

        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('get')
            ->with(ThemeExtension::THEME_KEY)
            ->will($this->returnValue($themeName));

        $update = $this->createMock(LayoutUpdateWithImports::class);
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

        /** @var Theme|\PHPUnit\Framework\MockObject\MockObject $theme */
        $theme = $this->getMockBuilder(Theme::class)->setConstructorArgs([$themeName])->getMock();
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

        /** @var ImportedLayoutUpdate|\PHPUnit\Framework\MockObject\MockObject $importUpdate */
        $importUpdate = $this->createMock(ImportedLayoutUpdate::class);

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
        array_unshift($expectedResult['root'], $importUpdate);

        $this->visitor->walkUpdates($updates, $context);
        $this->assertEquals($expectedResult, $updates);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testWalkUpdatesWithMultipleImportsOrdering()
    {
        $themeName = 'oro-import';

        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->exactly(2))
            ->method('get')
            ->with(ThemeExtension::THEME_KEY)
            ->will($this->returnValue($themeName));

        $updateWithImports = $this->createMock(LayoutUpdateWithImports::class);
        $updateWithImports->expects($this->once())
            ->method('getImports')
            ->will($this->returnValue(
                [
                    [
                        ImportsAwareLayoutUpdateInterface::ID_KEY => 'first_import',
                        ImportsAwareLayoutUpdateInterface::ROOT_KEY => 'root_block_id',
                        ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => 'import_namespace'
                    ],
                    [
                        ImportsAwareLayoutUpdateInterface::ID_KEY => 'second_import',
                        ImportsAwareLayoutUpdateInterface::ROOT_KEY => 'root_block_id',
                        ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => 'import_namespace'
                    ]
                ]
            ));

        $updateWithoutImports = $this->createMock(LayoutUpdateWithImports::class);
        $updateWithoutImports->expects($this->once())
            ->method('getImports')
            ->will($this->returnValue([]));

        /** @var Theme|\PHPUnit\Framework\MockObject\MockObject $theme */
        $theme = $this->getMockBuilder(Theme::class)->setConstructorArgs([$themeName])->getMock();
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
            [$theme->getName(), ImportVisitor::IMPORT_FOLDER, 'second_import']
        );

        $this->resourceProvider
            ->expects($this->at(0))
            ->method('findApplicableResources')
            ->with([$path])
            ->will($this->returnValue(['import/second_file']));

        $path = implode(
            DIRECTORY_SEPARATOR,
            [$theme->getName(), ImportVisitor::IMPORT_FOLDER, 'first_import']
        );

        $this->resourceProvider
            ->expects($this->at(1))
            ->method('findApplicableResources')
            ->with([$path])
            ->will($this->returnValue(['import/first_file']));

        /** @var ImportedLayoutUpdate|\PHPUnit\Framework\MockObject\MockObject $firstImportUpdate */
        $firstImportUpdate = $this->createMock(ImportedLayoutUpdate::class);

        /** @var ImportedLayoutUpdate|\PHPUnit\Framework\MockObject\MockObject $secondImportUpdate */
        $secondImportUpdate = $this->createMock(ImportedLayoutUpdate::class);

        $this->loader
            ->expects($this->at(0))
            ->method('load')
            ->with('import/second_file')
            ->will($this->returnValue($secondImportUpdate));

        $this->loader
            ->expects($this->at(1))
            ->method('load')
            ->with('import/first_file')
            ->will($this->returnValue($firstImportUpdate));

        $this->dependencyInitializer
            ->expects($this->at(0))
            ->method('initialize')
            ->with($secondImportUpdate);

        $this->dependencyInitializer
            ->expects($this->at(1))
            ->method('initialize')
            ->with($firstImportUpdate);

        $updates = ['root' => [
            $updateWithImports,
            $updateWithoutImports
        ]];

        $expectedResult = ['root' => [
            $secondImportUpdate,
            $firstImportUpdate,
            $updateWithImports,
            $updateWithoutImports
        ]];

        $this->visitor->walkUpdates($updates, $context);
        $this->assertSame($expectedResult, $updates);
    }

    public function testWalkUpdatesWithImportsContainedMultipleUpdates()
    {
        $themeName = 'oro-import';

        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('get')
            ->with(ThemeExtension::THEME_KEY)
            ->will($this->returnValue($themeName));

        $update = $this->createMock(LayoutUpdateWithImports::class);
        $update->expects($this->once())
            ->method('getImports')
            ->will($this->returnValue(
                ['import_id']
            ));

        /** @var Theme|\PHPUnit\Framework\MockObject\MockObject $theme */
        $theme = $this->getMockBuilder(Theme::class)->setConstructorArgs([$themeName])->getMock();
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

        /** @var ImportedLayoutUpdateWithImports|\PHPUnit\Framework\MockObject\MockObject $importUpdate */
        $importUpdate = $this->createMock(ImportedLayoutUpdateWithImports::class);
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
        array_unshift($expectedResult['root'], $importUpdate);

        $this->visitor->walkUpdates($updates, $context);
        $this->assertEquals($expectedResult, $updates);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Imports statement should be an array, string given
     */
    public function testWalkUpdatesWithNonArrayImports()
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);

        $update = $this->createMock(LayoutUpdateWithImports::class);
        $update->expects($this->once())
            ->method('getImports')
            ->will($this->returnValue('string'));

        $updates = ['root' => [$update]];

        $this->visitor->walkUpdates($updates, $context);
    }

    public function testImportsAreNotLoadedIfUpdateIsNotApplicable()
    {
        $update = new NotApplicableImportAwareLayoutUpdateStub();
        $updates = ['root' => [$update]];

        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);

        $this->themeManager->expects($this->never())->method('getTheme');

        $this->visitor->walkUpdates($updates, $context);
    }
}
