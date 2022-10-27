<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Configuration\Import\WorkflowImportProcessorSupervisor;
use Oro\Bundle\WorkflowBundle\Configuration\Import\WorkflowImportProcessorSupervisorFactory;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigFinderBuilder;
use Symfony\Component\Finder\Finder;

class WorkflowImportProcessorSupervisorFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowConfigFinderBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private WorkflowConfigFinderBuilder $finderBuilder;

    /** @var ConfigFileReaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private ConfigFileReaderInterface $reader;

    private WorkflowImportProcessorSupervisorFactory $factory;

    protected function setUp(): void
    {
        $this->finderBuilder = $this->createMock(WorkflowConfigFinderBuilder::class);
        $this->reader = $this->createMock(ConfigFileReaderInterface::class);

        $this->factory = new WorkflowImportProcessorSupervisorFactory($this->reader, $this->finderBuilder);
    }

    /**
     * @dataProvider applicabilityCases
     */
    public function testIsApplicable(array $import, bool $expected): void
    {
        self::assertEquals($expected, $this->factory->isApplicable($import));
    }

    public function applicabilityCases(): \Generator
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

    public function testCreate(): void
    {
        $file1 = new \SplFileInfo(__FILE__);
        $file1Content = [
            'workflows' => [
                'from' => ['a' => 1, 'node1' => 'replaced in to'],
                'to' => ['b' => 2]
            ]
        ];
        $processor = $this->factory->create(['workflow' => 'from', 'as' => 'to', 'replace' => ['node1']]);

        self::assertInstanceOf(WorkflowImportProcessorSupervisor::class, $processor);

        $finderMock = $this->createMock(Finder::class);
        $this->finderBuilder->expects(self::once())
            ->method('create')
            ->willReturn($finderMock);

        $finderMock->expects(self::once())
            ->method('getIterator')
            ->willReturn(new ArrayCollection([$file1]));

        $this->reader->expects(self::once())
            ->method('read')
            ->with($file1)
            ->willReturnOnConsecutiveCalls($file1Content);

        //as view is hidden and factory uses `new` - testing by indirect indications
        //e.g inner processor merges data correctly `from` to `to`, also replaces nodes unnecessary in `to`
        //so that factory configures objects properly
        $result = $processor->process($file1Content, $file1);

        self::assertEquals(
            [
                'workflows' => [
                    'from' => ['a' => 1, 'node1' => 'replaced in to'],
                    'to' => ['a' => 1, 'b' => 2]
                ]
            ],
            $result
        );
    }

    public function testCreateException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Can not create import processor. Import format is not supported.');
        $this->factory->create([42]);
    }
}
