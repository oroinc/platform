<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\Import\WorkflowImportProcessorSupervisor;
use Oro\Bundle\WorkflowBundle\Configuration\Import\WorkflowImportProcessorSupervisorFactory;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigFinderBuilder;

class WorkflowImportProcessorSupervisorFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var  WorkflowConfigFinderBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $finderBuilder;

    /** @var ConfigFileReaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $reader;

    /** @var WorkflowImportProcessorSupervisorFactory */
    private $factory;

    protected function setUp()
    {
        $this->finderBuilder = $this->createMock(WorkflowConfigFinderBuilder::class);
        $this->reader = $this->createMock(ConfigFileReaderInterface::class);

        $this->factory = new WorkflowImportProcessorSupervisorFactory($this->reader, $this->finderBuilder);
    }

    /**
     * @dataProvider applicabilityCases
     * @param mixed $import
     * @param bool $expected
     */
    public function testIsApplicable($import, bool $expected)
    {
        $this->assertEquals($expected, $this->factory->isApplicable($import));
    }

    /**
     * @return \Generator
     */
    public function applicabilityCases()
    {
        yield 'ok full' => [
            'import' => [
                'workflow' => 'from',
                'as' => 'to',
                'replace' => ['some.node.to.replace']
            ],
            true
        ];

        yield 'ok empty replace' => [
            'import' => [
                'workflow' => 'from',
                'as' => 'to',
                'replace' => []
            ],
            true
        ];

        yield 'nope. count. forget to set replace' => [
            'import' => [
                'workflow' => 'from',
                'as' => 'to'
            ],
            false
        ];

        yield 'nope. count. redundant options' => [
            'import' => [
                'workflow' => 'from',
                'as' => 'to',
                'replace' => ['node.a'],
                'resource' => ['./file'] //for example: another kind of import here
            ],
            false
        ];
    }

    public function testCreate()
    {
        $processor = $this->factory->create(['workflow' => 'from', 'as' => 'to', 'replace' => ['node1']]);

        $this->assertInstanceOf(WorkflowImportProcessorSupervisor::class, $processor);

        //as view is hidden and factory uses `new` - testing by indirect indications
        //e.g inner processor merges data correctly `from` to `to`, also replaces nodes unnecessary in `to`
        //so that factory configures objects properly
        $result = $processor->process(
            [
                'workflows' => [
                    'from' => ['a' => 1, 'node1' => 'replaced in to'],
                    'to' => ['b' => 2]
                ]
            ],
            new \SplFileInfo(__FILE__)
        );

        $this->assertEquals(
            [
                'workflows' => [
                    'from' => ['a' => 1, 'node1' => 'replaced in to'],
                    'to' => ['a' => 1, 'b' => 2]
                ]
            ],
            $result
        );
    }

    public function testCreateException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Can not create import processor. Import format is not supported.');
        $this->factory->create([42]);
    }
}
