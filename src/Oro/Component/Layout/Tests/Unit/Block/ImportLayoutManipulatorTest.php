<?php

namespace Oro\Component\Layout\Tests\Unit\Block;

use Oro\Component\Layout\ImportLayoutManipulator;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Oro\Component\Layout\Model\LayoutUpdateImport;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ImportLayoutManipulatorTest extends TestCase
{
    private ImportLayoutManipulator $importLayoutManipulator;
    private LayoutUpdateImport&MockObject $import;
    private LayoutManipulatorInterface&MockObject $layoutManipulator;

    #[\Override]
    protected function setUp(): void
    {
        $this->layoutManipulator = $this->createMock(LayoutManipulatorInterface::class);
        $this->import = $this->getImportMock('import_id', 'import_root', 'import_namespace');

        $this->importLayoutManipulator = new ImportLayoutManipulator($this->layoutManipulator, $this->import);
    }

    /**
     * Replace in parentId and siblingId
     */
    public function testAddWithRoot(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('add')
            ->with('__root', 'import_root', 'block', [], 'import_root');

        $this->importLayoutManipulator->add('__root', '__root', 'block', [], '__root');
    }

    /**
     * Replace in id, parentId and siblingId
     */
    public function testAddWithNamespace(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('add')
            ->with(
                'import_namespace_id',
                'import_namespace_parentId',
                'block',
                [
                    'additional_block_prefixes' => ['__import_id__id']
                ],
                'import_namespace_siblingId'
            );

        $this->importLayoutManipulator->add('__id', '__parentId', 'block', [], '__siblingId');
    }

    /**
     * Replace in id, parentId and siblingId
     */
    public function testAddWithParent(): void
    {
        $parentImport = $this->getImportMock('parent_import_id', 'parent_import_root', 'parent_import_namespace');
        $this->import->expects($this->any())
            ->method('getParent')
            ->willReturn($parentImport);

        $this->layoutManipulator->expects($this->once())
            ->method('add')
            ->with(
                'parent_import_namespace_import_namespace_id',
                'parent_import_namespace_import_namespace_parentId',
                'block',
                [
                    'additional_block_prefixes' => ['__import_id__id', '__parent_import_id__id']
                ],
                'parent_import_namespace_import_namespace_siblingId'
            );

        $this->importLayoutManipulator->add('__id', '__parentId', 'block', [], '__siblingId');
    }

    /**
     * Replace in id
     */
    public function testRemoveWithRoot(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('remove')
            ->with('import_root');

        $this->importLayoutManipulator->remove('__root');
    }

    /**
     * Replace in id
     */
    public function testRemoveWithNamespace(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('remove')
            ->with('import_namespace_id');

        $this->importLayoutManipulator->remove('__id');
    }

    /**
     * Replace in id
     */
    public function testRemoveWithParent(): void
    {
        $parentImport = $this->getImportMock('parent_import_id', 'parent_import_root', 'parent_import_namespace');
        $this->import->expects($this->any())
            ->method('getParent')
            ->willReturn($parentImport);

        $this->layoutManipulator->expects($this->once())
            ->method('remove')
            ->with('parent_import_namespace_import_namespace_id');

        $this->importLayoutManipulator->remove('__id');
    }

    /**
     * Replace in id, parentId and siblingId
     */
    public function testMoveWithRoot(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('move')
            ->with('import_root', 'import_root', 'import_root', true);

        $this->importLayoutManipulator->move('__root', '__root', '__root', true);
    }

    /**
     * Replace in id, parentId and siblingId
     */
    public function testMoveWithNamespace(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('move')
            ->with('import_namespace_id', 'import_namespace_parentId', 'import_namespace_siblingId');

        $this->importLayoutManipulator->move('__id', '__parentId', '__siblingId');
    }

    /**
     * Replace in id, parentId and siblingId
     */
    public function testMoveWithParent(): void
    {
        $parentImport = $this->getImportMock('parent_import_id', 'parent_import_root', 'parent_import_namespace');
        $this->import->expects($this->any())
            ->method('getParent')
            ->willReturn($parentImport);

        $this->layoutManipulator->expects($this->once())
            ->method('move')
            ->with(
                'parent_import_namespace_import_namespace_id',
                'parent_import_namespace_import_namespace_parentId',
                'parent_import_namespace_import_namespace_siblingId'
            );

        $this->importLayoutManipulator->move('__id', '__parentId', '__siblingId');
    }

    /**
     * Replace in id
     */
    public function testAddAliasWithRoot(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('addAlias')
            ->with('__root', 'import_root');

        $this->importLayoutManipulator->addAlias('__root', '__root');
    }

    /**
     * Replace in id and alias
     */
    public function testAddAliasWithNamespace(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('addAlias')
            ->with('import_namespace_alias', 'import_namespace_id');

        $this->importLayoutManipulator->addAlias('__alias', '__id');
    }

    /**
     * Replace in id and alias
     */
    public function testAddAliasWithParent(): void
    {
        $parentImport = $this->getImportMock('parent_import_id', 'parent_import_root', 'parent_import_namespace');
        $this->import->expects($this->any())
            ->method('getParent')
            ->willReturn($parentImport);

        $this->layoutManipulator->expects($this->once())
            ->method('addAlias')
            ->with('parent_import_namespace_import_namespace_alias', 'parent_import_namespace_import_namespace_id');

        $this->importLayoutManipulator->addAlias('__alias', '__id');
    }

    /**
     * Replace nothing
     */
    public function testRemoveAliasWithRoot(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('removeAlias')
            ->with('__root');

        $this->importLayoutManipulator->removeAlias('__root');
    }

    /**
     * Replace in alias
     */
    public function testRemoveAliasWithNamespace(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('removeAlias')
            ->with('import_namespace_alias');

        $this->importLayoutManipulator->removeAlias('__alias');
    }

    /**
     * Replace in alias
     */
    public function testRemoveAliasWithParent(): void
    {
        $parentImport = $this->getImportMock('parent_import_id', 'parent_import_root', 'parent_import_namespace');
        $this->import->expects($this->any())
            ->method('getParent')
            ->willReturn($parentImport);

        $this->layoutManipulator->expects($this->once())
            ->method('removeAlias')
            ->with('parent_import_namespace_import_namespace_alias');

        $this->importLayoutManipulator->removeAlias('__alias');
    }

    /**
     * Replace in id
     */
    public function testSetOptionWithRoot(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('setOption')
            ->with('import_root', 'optionName', 'optionValue');

        $this->importLayoutManipulator->setOption('__root', 'optionName', 'optionValue');
    }

    /**
     * Replace in id
     */
    public function testSetOptionWithNamespace(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('setOption')
            ->with('import_namespace_id', 'optionName', 'optionValue');

        $this->importLayoutManipulator->setOption('__id', 'optionName', 'optionValue');
    }

    /**
     * Replace in id
     */
    public function testSetOptionWithParent(): void
    {
        $parentImport = $this->getImportMock('parent_import_id', 'parent_import_root', 'parent_import_namespace');
        $this->import->expects($this->any())
            ->method('getParent')
            ->willReturn($parentImport);

        $this->layoutManipulator->expects($this->once())
            ->method('setOption')
            ->with('parent_import_namespace_import_namespace_id', 'optionName', 'optionValue');

        $this->importLayoutManipulator->setOption('__id', 'optionName', 'optionValue');
    }

    /**
     * Replace in id
     */
    public function testAppendOptionWithRoot(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('appendOption')
            ->with('import_root', 'optionName', 'optionValue');

        $this->importLayoutManipulator->appendOption('__root', 'optionName', 'optionValue');
    }

    /**
     * Replace in id
     */
    public function testAppendOptionWithNamespace(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('appendOption')
            ->with('import_namespace_id', 'optionName', 'optionValue');

        $this->importLayoutManipulator->appendOption('__id', 'optionName', 'optionValue');
    }

    /**
     * Replace in id
     */
    public function testAppendOptionWithParent(): void
    {
        $parentImport = $this->getImportMock('parent_import_id', 'parent_import_root', 'parent_import_namespace');
        $this->import->expects($this->any())
            ->method('getParent')
            ->willReturn($parentImport);

        $this->layoutManipulator->expects($this->once())
            ->method('appendOption')
            ->with('parent_import_namespace_import_namespace_id', 'optionName', 'optionValue');

        $this->importLayoutManipulator->appendOption('__id', 'optionName', 'optionValue');
    }

    /**
     * Replace in id
     */
    public function testSubtractOptionWithRoot(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('subtractOption')
            ->with('import_root', 'optionName', 'optionValue');

        $this->importLayoutManipulator->subtractOption('__root', 'optionName', 'optionValue');
    }

    /**
     * Replace in id
     */
    public function testSubtractOptionWithNamespace(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('subtractOption')
            ->with('import_namespace_id', 'optionName', 'optionValue');

        $this->importLayoutManipulator->subtractOption('__id', 'optionName', 'optionValue');
    }

    /**
     * Replace in id
     */
    public function testSubtractOptionWithParent(): void
    {
        $parentImport = $this->getImportMock('parent_import_id', 'parent_import_root', 'parent_import_namespace');
        $this->import->expects($this->any())
            ->method('getParent')
            ->willReturn($parentImport);

        $this->layoutManipulator->expects($this->once())
            ->method('subtractOption')
            ->with('parent_import_namespace_import_namespace_id', 'optionName', 'optionValue');

        $this->importLayoutManipulator->subtractOption('__id', 'optionName', 'optionValue');
    }

    /**
     * Replace in id
     */
    public function testReplaceOptionWithRoot(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('replaceOption')
            ->with('import_root', 'optionName', 'optionValue', 'newOptionValue');

        $this->importLayoutManipulator->replaceOption('__root', 'optionName', 'optionValue', 'newOptionValue');
    }

    /**
     * Replace in id
     */
    public function testReplaceOptionWithNamespace(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('replaceOption')
            ->with('import_namespace_id', 'optionName', 'optionValue', 'newOptionValue');

        $this->importLayoutManipulator->replaceOption('__id', 'optionName', 'optionValue', 'newOptionValue');
    }

    /**
     * Replace in id
     */
    public function testReplaceOptionWithParent(): void
    {
        $parentImport = $this->getImportMock('parent_import_id', 'parent_import_root', 'parent_import_namespace');
        $this->import->expects($this->any())
            ->method('getParent')
            ->willReturn($parentImport);

        $this->layoutManipulator->expects($this->once())
            ->method('replaceOption')
            ->with('parent_import_namespace_import_namespace_id', 'optionName', 'optionValue', 'newOptionValue');

        $this->importLayoutManipulator->replaceOption('__id', 'optionName', 'optionValue', 'newOptionValue');
    }

    /**
     * Replace in id
     */
    public function testRemoveOptionWithRoot(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('removeOption')
            ->with('import_root', 'optionName');

        $this->importLayoutManipulator->removeOption('__root', 'optionName');
    }

    /**
     * Replace in id
     */
    public function testRemoveOptionWithNamespace(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('removeOption')
            ->with('import_namespace_id', 'optionName');

        $this->importLayoutManipulator->removeOption('__id', 'optionName');
    }

    /**
     * Replace in id
     */
    public function testRemoveOptionWithParent(): void
    {
        $parentImport = $this->getImportMock('parent_import_id', 'parent_import_root', 'parent_import_namespace');
        $this->import->expects($this->any())
            ->method('getParent')
            ->willReturn($parentImport);

        $this->layoutManipulator->expects($this->once())
            ->method('removeOption')
            ->with('parent_import_namespace_import_namespace_id', 'optionName');

        $this->importLayoutManipulator->removeOption('__id', 'optionName');
    }

    /**
     * Replace in id
     */
    public function testChangeBlockTypeWithRoot(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('changeBlockType')
            ->with('import_root', 'container');

        $this->importLayoutManipulator->changeBlockType('__root', 'container');
    }

    /**
     * Replace in id
     */
    public function testChangeBlockTypeWithNamespace(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('changeBlockType')
            ->with('import_namespace_id', 'optionName', 'optionValue');

        $this->importLayoutManipulator->changeBlockType('__id', 'optionName', 'optionValue');
    }

    /**
     * Replace in id
     */
    public function testChangeBlockTypeWithParent(): void
    {
        $parentImport = $this->getImportMock('parent_import_id', 'parent_import_root', 'parent_import_namespace');
        $this->import->expects($this->any())
            ->method('getParent')
            ->willReturn($parentImport);

        $this->layoutManipulator->expects($this->once())
            ->method('changeBlockType')
            ->with('parent_import_namespace_import_namespace_id', 'optionName', 'optionValue');

        $this->importLayoutManipulator->changeBlockType('__id', 'optionName', 'optionValue');
    }

    /**
     * Replace in id
     */
    public function testSetBlockThemeWithRoot(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('setBlockTheme')
            ->with('block_theme', 'import_root');

        $this->importLayoutManipulator->setBlockTheme('block_theme', '__root');
    }

    /**
     * Replace in id
     */
    public function testSetBlockThemeWithNamespace(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('setBlockTheme')
            ->with('block_theme', 'import_namespace_id');

        $this->importLayoutManipulator->setBlockTheme('block_theme', '__id');
    }

    /**
     * Replace in id
     */
    public function testSetBlockThemeWithParent(): void
    {
        $parentImport = $this->getImportMock('parent_import_id', 'parent_import_root', 'parent_import_namespace');
        $this->import->expects($this->any())
            ->method('getParent')
            ->willReturn($parentImport);

        $this->layoutManipulator->expects($this->once())
            ->method('setBlockTheme')
            ->with('block_theme', 'parent_import_namespace_import_namespace_id');

        $this->importLayoutManipulator->setBlockTheme('block_theme', '__id');
    }

    /**
     * Replace in id
     */
    public function testSetFormTheme(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('setFormTheme')
            ->with('form_theme');

        $this->importLayoutManipulator->setFormTheme('form_theme');
    }

    /**
     * Replace in id
     */
    public function testClear(): void
    {
        $this->layoutManipulator->expects($this->once())
            ->method('clear');

        $this->importLayoutManipulator->clear();
    }

    private function getImportMock(string $id, string $root, string $namespace): LayoutUpdateImport&MockObject
    {
        $import = $this->createMock(LayoutUpdateImport::class);
        $import->expects($this->any())
            ->method('getId')
            ->willReturn($id);
        $import->expects($this->any())
            ->method('getRoot')
            ->willReturn($root);
        $import->expects($this->any())
            ->method('getNamespace')
            ->willReturn($namespace);

        return $import;
    }
}
