<?php

namespace Oro\Component\Layout\Tests\Unit\Loader\Generator\Extension;

use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorInterface;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use Oro\Component\Layout\Loader\Generator\Extension\ImportsLayoutUpdateExtension;

class ImportsLayoutUpdateExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ImportsLayoutUpdateExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new ImportsLayoutUpdateExtension();
    }

    /**
     * @dataProvider prepareDataProvider
     * @param array $source
     * @param array $expectedData
     */
    public function testPrepare(array $source, array $expectedData)
    {
        $collection = new VisitorCollection();
        $this->extension->prepare(new GeneratorData($source), $collection);
        $this->assertSameSize($expectedData, $collection);
        foreach ($collection as $visitor) {
            /** @var VisitorInterface $visitor */
            $class = new \ReflectionClass(
                'Oro\Component\Layout\Loader\Generator\Extension\ImportsLayoutUpdateVisitor'
            );
            $property = $class->getProperty('import');
            $property->setAccessible(true);
            $this->assertContains($property->getValue($visitor), $expectedData);
        }
    }

    /**
     * @return array
     */
    public function prepareDataProvider()
    {
        return [
            [
                'source' => [],
                'expectedData' => [],
            ],
            [
                'source' => [
                    ImportsLayoutUpdateExtension::NODE_CONDITIONS => []
                ],
                'expectedData' => [],
            ],
            [
                'source' => [
                    ImportsLayoutUpdateExtension::NODE_CONDITIONS => [
                        'id' => 'import_id',
                        'root' => 'root_block_id',
                        'namespace' => 'import_namespace',
                    ]
                ],
                'expectedData' => [
                    [
                    'id' => 'import_id',
                    'root' => 'root_block_id',
                    'namespace' => 'import_namespace',
                    ]
                ],
            ]
        ];
    }
}
