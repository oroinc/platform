<?php

namespace Oro\Component\Layout\Tests\Unit\Loader\Generator\Extension;

use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use Oro\Component\Layout\Loader\Generator\Extension\ImportsLayoutUpdateExtension;
use Oro\Component\Layout\Loader\Generator\Extension\ImportLayoutUpdateVisitor;
use Oro\Component\Layout\Loader\Generator\Extension\ImportsAwareLayoutUpdateVisitor;

class ImportsLayoutUpdateExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ImportsLayoutUpdateExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new ImportsLayoutUpdateExtension();
    }

    public function testPrepareWithNodeImports()
    {
        $source = [
            ImportsLayoutUpdateExtension::NODE_IMPORTS => [
                [
                    'id'        => 'import_id',
                    'root'      => 'root_block_id',
                    'namespace' => 'import_namespace',
                ],
                [
                    'id'        => 'import_id_2',
                    'root'      => 'root_block_id_2',
                    'namespace' => 'import_namespace_2',
                ]
            ]
        ];
        $imports = [
            [
                'id'        => 'import_id',
                'root'      => 'root_block_id',
                'namespace' => 'import_namespace',
            ],
            [
                'id'        => 'import_id_2',
                'root'      => 'root_block_id_2',
                'namespace' => 'import_namespace_2',
            ]
        ];
        $collection = $this->getMock(VisitorCollection::class);
        $collection->expects($this->once())
            ->method('append')
            ->with(new ImportsAwareLayoutUpdateVisitor($imports));

        $this->extension->prepare(new GeneratorData($source), $collection);
    }

    /**
     * @dataProvider prepareWithoutNodeImportsDataProvider
     *
     * @param array $source
     */
    public function testPrepareWithoutNodeImports(array $source)
    {
        $collection = $this->getMock(VisitorCollection::class);
        $collection->expects($this->never())
            ->method('append');
        $this->extension->prepare(new GeneratorData($source), $collection);
    }

    /**
     * @return array
     */
    public function prepareWithoutNodeImportsDataProvider()
    {
        return [
            'without imports'    => [
                'source' => [],
            ],
            'with empty imports' => [
                'source' => [
                    ImportsLayoutUpdateExtension::NODE_IMPORTS => []
                ]
            ],
        ];
    }

    public function testPrepareWithImportedLayoutUpdate()
    {
        $filename = 'default/imports/import_name/filename.yml';

        $collection = $this->getMock(VisitorCollection::class);
        $collection->expects($this->once())
            ->method('append')
            ->with(new ImportLayoutUpdateVisitor());

        $this->extension->prepare(new GeneratorData([], $filename), $collection);
    }

    public function testPrepareWithNotImportedLayoutUpdate()
    {
        $filename = 'default/not_imported/import_name/filename.yml';

        $collection = $this->getMock(VisitorCollection::class);
        $collection->expects($this->never())
            ->method('append')
            ->with(new ImportLayoutUpdateVisitor());

        $this->extension->prepare(new GeneratorData([], $filename), $collection);
    }

    public function testPrepareImportedLayoutUpdateWithImports()
    {
        $filename = 'default/imports/import_name/filename.yml';
        $source = [
            ImportsLayoutUpdateExtension::NODE_IMPORTS => [
                [
                    'id'        => 'import_id',
                    'root'      => 'root_block_id',
                    'namespace' => 'import_namespace',
                ]
            ]
        ];
        $imports = [
            [
                'id'        => 'import_id',
                'root'      => 'root_block_id',
                'namespace' => 'import_namespace',
            ]
        ];
        $collection = $this->getMock(VisitorCollection::class);
        $collection->expects($this->at(0))
            ->method('append')
            ->with(new ImportsAwareLayoutUpdateVisitor($imports));
        $collection->expects($this->at(1))
            ->method('append')
            ->with(new ImportLayoutUpdateVisitor());

        $this->extension->prepare(new GeneratorData($source, $filename), $collection);

    }
}
