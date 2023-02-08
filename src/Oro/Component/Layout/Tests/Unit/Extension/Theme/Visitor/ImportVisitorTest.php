<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Visitor;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Exception\LogicException;
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
    private LayoutUpdateLoaderInterface|\PHPUnit\Framework\MockObject\MockObject $loader;

    private DependencyInitializer|\PHPUnit\Framework\MockObject\MockObject $dependencyInitializer;

    private ResourceProviderInterface|\PHPUnit\Framework\MockObject\MockObject $resourceProvider;

    private ThemeManager|\PHPUnit\Framework\MockObject\MockObject $themeManager;

    private ImportVisitor $visitor;

    protected function setUp(): void
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

    public function testWalkUpdatesWithoutImports(): void
    {
        $context = $this->createMock(ContextInterface::class);

        $updates = ['root' => [$this->createMock(LayoutUpdateInterface::class)]];

        $this->visitor->walkUpdates($updates, $context);
        self::assertEquals($updates, $updates);
    }

    public function testWalkUpdatesWithImports(): void
    {
        $themeName = 'oro-import';

        $context = $this->createMock(ContextInterface::class);
        $context->expects(self::once())
            ->method('get')
            ->with(ThemeExtension::THEME_KEY)
            ->willReturn($themeName);

        $update = $this->createMock(LayoutUpdateWithImports::class);
        $update->expects(self::once())
            ->method('getImports')
            ->willReturn([
                [
                    ImportsAwareLayoutUpdateInterface::ID_KEY => 'import_id',
                    ImportsAwareLayoutUpdateInterface::ROOT_KEY => 'root_block_id',
                    ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => 'import_namespace',
                ],
            ]);

        $theme = $this->getMockBuilder(Theme::class)->setConstructorArgs([$themeName])->getMock();
        $theme->expects(self::any())
            ->method('getName')
            ->willReturn($themeName);

        $this->themeManager->expects(self::any())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $path = implode(
            DIRECTORY_SEPARATOR,
            [$theme->getName(), ImportVisitor::IMPORT_FOLDER, 'import_id']
        );

        $this->resourceProvider->expects(self::once())
            ->method('findApplicableResources')
            ->with([$path])
            ->willReturn(['import/file']);

        $importUpdate = $this->createMock(ImportedLayoutUpdate::class);

        $this->loader->expects(self::once())
            ->method('load')
            ->with('import/file')
            ->willReturn($importUpdate);

        $this->dependencyInitializer->expects(self::once())
            ->method('initialize')
            ->with($importUpdate);

        $updates = ['root' => [$update]];

        $expectedResult = $updates;
        array_unshift($expectedResult['root'], $importUpdate);

        $this->visitor->walkUpdates($updates, $context);
        self::assertEquals($expectedResult, $updates);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testWalkUpdatesWithMultipleImportsOrdering(): void
    {
        $themeName = 'oro-import';

        $context = $this->createMock(ContextInterface::class);
        $context->expects(self::exactly(2))
            ->method('get')
            ->with(ThemeExtension::THEME_KEY)
            ->willReturn($themeName);

        $updateWithImports = $this->createMock(LayoutUpdateWithImports::class);
        $updateWithImports->expects(self::once())
            ->method('getImports')
            ->willReturn([
                [
                    ImportsAwareLayoutUpdateInterface::ID_KEY => 'first_import',
                    ImportsAwareLayoutUpdateInterface::ROOT_KEY => 'root_block_id',
                    ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => 'import_namespace',
                ],
                [
                    ImportsAwareLayoutUpdateInterface::ID_KEY => 'second_import',
                    ImportsAwareLayoutUpdateInterface::ROOT_KEY => 'root_block_id',
                    ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => 'import_namespace',
                ],
            ]);

        $updateWithoutImports = $this->createMock(LayoutUpdateWithImports::class);
        $updateWithoutImports->expects(self::once())
            ->method('getImports')
            ->willReturn([]);

        $theme = $this->getMockBuilder(Theme::class)->setConstructorArgs([$themeName])->getMock();
        $theme->expects(self::any())
            ->method('getName')
            ->willReturn($themeName);

        $this->themeManager->expects(self::any())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $firstImportPath = implode(
            DIRECTORY_SEPARATOR,
            [$theme->getName(), ImportVisitor::IMPORT_FOLDER, 'first_import']
        );
        $secondImportPath = implode(
            DIRECTORY_SEPARATOR,
            [$theme->getName(), ImportVisitor::IMPORT_FOLDER, 'second_import']
        );
        $this->resourceProvider->expects(self::exactly(2))
            ->method('findApplicableResources')
            ->withConsecutive([[$secondImportPath]], [[$firstImportPath]])
            ->willReturnOnConsecutiveCalls(['import/second_file'], ['import/first_file']);

        $firstImportUpdate = $this->createMock(ImportedLayoutUpdate::class);
        $secondImportUpdate = $this->createMock(ImportedLayoutUpdate::class);
        $this->loader->expects(self::exactly(2))
            ->method('load')
            ->withConsecutive(['import/second_file'], ['import/first_file'])
            ->willReturnOnConsecutiveCalls($secondImportUpdate, $firstImportUpdate);
        $this->dependencyInitializer->expects(self::exactly(2))
            ->method('initialize')
            ->withConsecutive([$secondImportUpdate], [$firstImportUpdate]);

        $updates = [
            'root' => [
                $updateWithImports,
                $updateWithoutImports,
            ],
        ];

        $expectedResult = [
            'root' => [
                $secondImportUpdate,
                $firstImportUpdate,
                $updateWithImports,
                $updateWithoutImports,
            ],
        ];

        $this->visitor->walkUpdates($updates, $context);
        self::assertSame($expectedResult, $updates);
    }

    public function testWalkUpdatesWithImportsContainedMultipleUpdates(): void
    {
        $themeName = 'oro-import';

        $context = $this->createMock(ContextInterface::class);
        $context->expects(self::once())
            ->method('get')
            ->with(ThemeExtension::THEME_KEY)
            ->willReturn($themeName);

        $update = $this->createMock(LayoutUpdateWithImports::class);
        $update->expects(self::once())
            ->method('getImports')
            ->willReturn(['import_id']);

        $theme = $this->getMockBuilder(Theme::class)->setConstructorArgs([$themeName])->getMock();
        $theme->expects(self::any())
            ->method('getName')
            ->willReturn($themeName);

        $this->themeManager->expects(self::any())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $path = implode(
            DIRECTORY_SEPARATOR,
            [$theme->getName(), ImportVisitor::IMPORT_FOLDER, 'import_id']
        );

        $this->resourceProvider->expects(self::once())
            ->method('findApplicableResources')
            ->with([$path])
            ->willReturn(['import/file']);

        $importUpdate = $this->createMock(ImportedLayoutUpdateWithImports::class);
        $importUpdate->expects(self::once())
            ->method('getImports')
            ->willReturn([]);

        $this->loader->expects(self::once())
            ->method('load')
            ->with('import/file')
            ->willReturn($importUpdate);

        $this->dependencyInitializer->expects(self::once())
            ->method('initialize')
            ->with($importUpdate);

        $updates = ['root' => [$update]];

        $expectedResult = $updates;
        array_unshift($expectedResult['root'], $importUpdate);

        $this->visitor->walkUpdates($updates, $context);
        self::assertEquals($expectedResult, $updates);
    }

    public function testWalkUpdatesWithNonArrayImports(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Imports statement should be an array, string given');

        $context = $this->createMock(ContextInterface::class);

        $update = $this->createMock(LayoutUpdateWithImports::class);
        $update->expects(self::once())
            ->method('getImports')
            ->willReturn('string');

        $updates = ['root' => [$update]];

        $this->visitor->walkUpdates($updates, $context);
    }

    public function testImportsAreNotLoadedIfUpdateIsNotApplicable(): void
    {
        $update = new NotApplicableImportAwareLayoutUpdateStub();
        $updates = ['root' => [$update]];

        $context = $this->createMock(ContextInterface::class);

        $this->themeManager->expects(self::never())
            ->method('getTheme');

        $this->visitor->walkUpdates($updates, $context);
    }

    public function testWalkUpdatesWithImportsAndCustomRoot(): void
    {
        $themeName = 'oro-import';

        $context = $this->createMock(ContextInterface::class);
        $context->expects(self::once())
            ->method('get')
            ->with(ThemeExtension::THEME_KEY)
            ->willReturn($themeName);

        $update = $this->createMock(LayoutUpdateWithImports::class);
        $update->expects(self::once())
            ->method('getImports')
            ->willReturn([
                [
                    ImportsAwareLayoutUpdateInterface::ID_KEY => 'import_id',
                    ImportsAwareLayoutUpdateInterface::ROOT_KEY => 'root_block_id',
                    ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => 'import_namespace',
                ],
            ]);

        $theme = $this->getMockBuilder(Theme::class)->setConstructorArgs([$themeName])->getMock();
        $theme->expects(self::any())
            ->method('getName')
            ->willReturn($themeName);

        $this->themeManager->expects(self::any())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $path = implode(
            DIRECTORY_SEPARATOR,
            [$theme->getName(), ImportVisitor::IMPORT_FOLDER, 'import_id']
        );

        $this->resourceProvider->expects(self::once())
            ->method('findApplicableResources')
            ->with([$path])
            ->willReturn(['import/file']);

        $importUpdate = $this->createMock(ImportedLayoutUpdate::class);

        $this->loader->expects(self::once())
            ->method('load')
            ->with('import/file')
            ->willReturn($importUpdate);

        $this->dependencyInitializer->expects(self::once())
            ->method('initialize')
            ->with($importUpdate);

        $updates = ['custom_root' => [$update]];

        $expectedResult = $updates;
        array_unshift($expectedResult['custom_root'], $importUpdate);

        $this->visitor->walkUpdates($updates, $context);
        self::assertEquals($expectedResult, $updates);
    }
}
