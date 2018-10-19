<?php

namespace Oro\Component\Layout\Tests\Unit\Model;

use Oro\Component\Layout\ImportsAwareLayoutUpdateInterface;
use Oro\Component\Layout\Model\LayoutUpdateImport;

class LayoutUpdateImportTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters()
    {
        $id = 'import_id';
        $root = 'root_block_id';
        $namespace = 'import_namespace';
        $parent = new LayoutUpdateImport('parent_id', 'parent_root', 'parent_namespace');
        $import = new LayoutUpdateImport($id, $root, $namespace);
        $import->setParent($parent);
        $this->assertEquals($id, $import->getId());
        $this->assertEquals($root, $import->getRoot());
        $this->assertEquals($namespace, $import->getNamespace());
        $this->assertEquals($parent, $import->getParent());
        $this->assertEquals($parent->getId(), $import->getParent()->getId());
    }

    public function testCreateFromArray()
    {
        $id = 'import_id';
        $root = 'root_block_id';
        $namespace = 'import_namespace';
        $import = LayoutUpdateImport::createFromArray([
            ImportsAwareLayoutUpdateInterface::ID_KEY => $id,
            ImportsAwareLayoutUpdateInterface::ROOT_KEY => $root,
            ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => $namespace,
        ]);
        $this->assertEquals($id, $import->getId());
        $this->assertEquals($root, $import->getRoot());
        $this->assertEquals($namespace, $import->getNamespace());
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Import id should be provided, array with "root, namespace" keys given
     */
    public function testCreateFromArrayException()
    {
        LayoutUpdateImport::createFromArray([
            ImportsAwareLayoutUpdateInterface::ROOT_KEY => 'root_block_id',
            ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => 'namespace',
        ]);
    }

    public function testToArray()
    {
        $data = [
            ImportsAwareLayoutUpdateInterface::ID_KEY => 'import_id',
            ImportsAwareLayoutUpdateInterface::ROOT_KEY => 'root_block_id',
            ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => 'import_namespace',
        ];
        $this->assertEquals($data, LayoutUpdateImport::createFromArray($data)->toArray());
    }
}
