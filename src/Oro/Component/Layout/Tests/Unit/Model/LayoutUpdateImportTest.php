<?php

namespace Oro\Component\Layout\Tests\Unit\Model;

use Oro\Component\Layout\Model\LayoutUpdateImport;

class LayoutUpdateImportTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $id = 'import_id';
        $root = 'root_block_id';
        $namespace = 'import_namespace';
        $import = new LayoutUpdateImport($id, $root, $namespace);
        $this->assertEquals($id, $import->getId());
        $this->assertEquals($root, $import->getRoot());
        $this->assertEquals($namespace, $import->getNamespace());
    }
}
