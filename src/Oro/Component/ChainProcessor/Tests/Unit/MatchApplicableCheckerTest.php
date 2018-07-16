<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\MatchApplicableChecker;
use Oro\Component\ChainProcessor\ProcessorFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorIterator;

class MatchApplicableCheckerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testMatchApplicableChecker()
    {
        $context = new Context();
        $context->setAction('action1');
        $context->set('class', 'TestCls');
        $context->set('type', 'test');
        $context->set('feature', ['feature1', 'feature3']);
        $context->set('featureObj', new TestArrayObject(['feature1', 'feature3']));

        $processors = [
            [
                'processor1',
                []
            ],
            [
                'processor2',
                ['class' => 'TestCls']
            ],
            [
                'processor3',
                ['type' => 'test']
            ],
            [
                'processor4',
                ['class' => 'TestCls', 'type' => 'test']
            ],
            [
                'processor5',
                ['class' => 'TestCls', 'type' => 'test', 'another' => 'val']
            ],
            [
                'processor6',
                ['class' => 'AnotherCls']
            ],
            [
                'processor7',
                ['type' => 'test']
            ],
            [
                'processor8',
                ['class' => 'AnotherCls', 'type' => 'test']
            ],
            [
                'processor9',
                ['class' => 'AnotherCls', 'type' => 'test', 'another' => 'val']
            ],
            [
                'processor10',
                ['class' => 'TestCls']
            ],
            [
                'processor11',
                ['type' => 'another']
            ],
            [
                'processor12',
                ['class' => 'TestCls', 'type' => 'another']
            ],
            [
                'processor13',
                ['class' => 'TestCls', 'type' => 'another', 'another' => 'val']
            ],
            [
                'processor14',
                ['class' => 'TestCls', 'feature' => 'feature1']
            ],
            [
                'processor15',
                ['class' => 'TestCls', 'feature' => 'feature2']
            ],
            [
                'processor16',
                ['class' => 'TestCls', 'feature' => 'feature3']
            ],
            [
                'processor17',
                ['class' => 'TestCls', 'feature' => ['&' => ['feature1', 'feature3']]]
            ],
            [
                'processor18',
                ['class' => 'TestCls', 'feature' => ['&' => ['feature3', 'feature1']]]
            ],
            [
                'processor19',
                ['class' => 'TestCls', 'feature' => ['&' => ['feature1', 'feature2']]]
            ],
            [
                'processor20',
                ['type' => ['!' => 'test']]
            ],
            [
                'processor21',
                ['type' => ['!' => 'test1']]
            ],
            [
                'processor22',
                ['feature' => '!feature1']
            ],
            [
                'processor23',
                ['feature' => ['!' => 'feature2']]
            ],
            [
                'processor24',
                ['feature' => ['&' => [['!' => 'feature1'], ['!' => 'feature2']]]]
            ],
            [
                'processor25',
                ['feature' => ['&' => ['feature1', ['!' => 'feature2']]]]
            ],
            [
                'processor26',
                ['feature' => ['&' => [['!' => 'feature1'], 'feature2']]]
            ],
            [
                'processor27',
                ['feature' => ['&' => [['!' => 'feature1'], ['!' => 'feature3']]]]
            ],
            [
                'processor28',
                ['feature' => ['&' => [['!' => 'feature2'], ['!' => 'feature4']]]]
            ],
            [
                'processor29',
                ['type' => ['&' => [['!' => 'test'], ['!' => 'test1']]]]
            ],
            [
                'processor30',
                ['type' => ['&' => ['test', ['!' => 'test1']]]]
            ],
            [
                'processor31',
                ['type' => ['&' => [['!' => 'test'], 'test1']]]
            ],
            [
                'processor32',
                ['type' => ['&' => ['test', 'test1']]]
            ],
            [
                'processor33',
                ['class' => 'TestCls', 'featureObj' => ['&' => ['feature1', 'feature3']]]
            ],
            [
                'processor34',
                ['class' => 'TestCls', 'featureObj' => ['&' => ['feature3', 'feature1']]]
            ],
            [
                'processor35',
                ['class' => 'TestCls', 'featureObj' => ['&' => ['feature1', 'feature2']]]
            ],
            [
                'processor36',
                ['feature' => ['|' => [['!' => 'feature1'], ['!' => 'feature2']]]]
            ],
            [
                'processor37',
                ['feature' => ['|' => ['feature1', ['!' => 'feature2']]]]
            ],
            [
                'processor38',
                ['feature' => ['|' => [['!' => 'feature1'], 'feature2']]]
            ],
            [
                'processor39',
                ['feature' => ['|' => [['!' => 'feature1'], ['!' => 'feature3']]]]
            ],
            [
                'processor40',
                ['feature' => ['|' => [['!' => 'feature2'], ['!' => 'feature4']]]]
            ],
            [
                'processor41',
                ['type' => ['|' => [['!' => 'test'], ['!' => 'test1']]]]
            ],
            [
                'processor42',
                ['type' => ['|' => ['test', ['!' => 'test1']]]]
            ],
            [
                'processor43',
                ['type' => ['|' => [['!' => 'test'], 'test1']]]
            ],
            [
                'processor44',
                ['type' => ['|' => ['test', 'test1']]]
            ],
            [
                'processor45',
                ['class' => 'TestCls', 'featureObj' => ['|' => ['feature1', 'feature3']]]
            ],
            [
                'processor46',
                ['class' => 'TestCls', 'featureObj' => ['|' => ['feature3', 'feature1']]]
            ],
            [
                'processor47',
                ['class' => 'TestCls', 'featureObj' => ['|' => ['feature1', 'feature2']]]
            ],
        ];

        $iterator = new ProcessorIterator(
            $processors,
            $context,
            $this->getApplicableChecker(),
            $this->getProcessorFactory()
        );

        $expected = [
            'processor1',
            'processor2',
            'processor3',
            'processor4',
            'processor5',
            'processor7',
            'processor10',
            'processor14',
            'processor16',
            'processor17',
            'processor18',
            'processor21',
            'processor23',
            'processor25',
            'processor28',
            'processor30',
            'processor33',
            'processor34',
            'processor36',
            'processor37',
            'processor40',
            'processor41',
            'processor42',
            'processor44',
            'processor45',
            'processor46',
            'processor47',
        ];
        $this->assertProcessors($expected, $iterator);
        // test that iterator state is not changed
        $this->assertProcessors($expected, $iterator);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testMatchApplicableCheckerWithCustomApplicableChecker()
    {
        $context = new Context();
        $context->setAction('action1');
        $context->set('class', 'TestCls');
        $context->set('type', 'test');

        $processors = [
            [
                'processor1',
                []
            ],
            [
                'processor1_disabled',
                ['disabled' => true]
            ],
            [
                'processor2',
                ['class' => 'TestCls']
            ],
            [
                'processor2_disabled',
                ['disabled' => true, 'class' => 'TestCls']
            ],
            [
                'processor3',
                ['type' => 'test']
            ],
            [
                'processor3_disabled',
                ['disabled' => true, 'type' => 'test']
            ],
            [
                'processor4',
                ['class' => 'TestCls', 'type' => 'test']
            ],
            [
                'processor4_disabled',
                ['disabled' => true, 'class' => 'TestCls', 'type' => 'test']
            ],
            [
                'processor5',
                ['class' => 'TestCls', 'type' => 'test', 'another' => 'val']
            ],
            [
                'processor5_disabled',
                ['disabled' => true, 'class' => 'TestCls', 'type' => 'test', 'another' => 'val']
            ],
            [
                'processor6',
                ['class' => 'AnotherCls']
            ],
            [
                'processor6_disabled',
                ['disabled' => true, 'class' => 'AnotherCls']
            ],
            [
                'processor7',
                ['type' => 'test']
            ],
            [
                'processor7_disabled',
                ['disabled' => true, 'type' => 'test']
            ],
            [
                'processor8',
                ['class' => 'AnotherCls', 'type' => 'test']
            ],
            [
                'processor8_disabled',
                ['disabled' => true, 'class' => 'AnotherCls', 'type' => 'test']
            ],
            [
                'processor9',
                ['class' => 'AnotherCls', 'type' => 'test', 'another' => 'val']
            ],
            [
                'processor9_disabled',
                ['disabled' => true, 'class' => 'AnotherCls', 'type' => 'test', 'another' => 'val']
            ],
            [
                'processor10',
                ['class' => 'TestCls']
            ],
            [
                'processor10_disabled',
                ['disabled' => true, 'class' => 'TestCls']
            ],
            [
                'processor11',
                ['type' => 'another']
            ],
            [
                'processor11_disabled',
                ['disabled' => true, 'type' => 'another']
            ],
            [
                'processor12',
                ['class' => 'TestCls', 'type' => 'another']
            ],
            [
                'processor12_disabled',
                ['disabled' => true, 'class' => 'TestCls', 'type' => 'another']
            ],
            [
                'processor13',
                ['class' => 'TestCls', 'type' => 'another', 'another' => 'val']
            ],
            [
                'processor13_disabled',
                ['disabled' => true, 'class' => 'TestCls', 'type' => 'another', 'another' => 'val']
            ],
        ];

        $applicableChecker = $this->getApplicableChecker();
        $applicableChecker->addChecker(new NotDisabledApplicableChecker());
        $iterator = new ProcessorIterator(
            $processors,
            $context,
            $applicableChecker,
            $this->getProcessorFactory()
        );

        $this->assertProcessors(
            [
                'processor1',
                'processor2',
                'processor3',
                'processor4',
                'processor5',
                'processor7',
                'processor10',
            ],
            $iterator
        );
    }

    /**
     * @return ChainApplicableChecker
     */
    protected function getApplicableChecker()
    {
        $checker = new ChainApplicableChecker();
        $checker->addChecker(new MatchApplicableChecker());

        return $checker;
    }

    /**
     * @return ProcessorFactoryInterface
     */
    protected function getProcessorFactory()
    {
        $factory = $this->createMock('Oro\Component\ChainProcessor\ProcessorFactoryInterface');
        $factory->expects($this->any())
            ->method('getProcessor')
            ->willReturnCallback(
                function ($processorId) {
                    return new ProcessorMock($processorId);
                }
            );

        return $factory;
    }

    /**
     * @param string[]  $expectedProcessorIds
     * @param \Iterator $processors
     */
    protected function assertProcessors(array $expectedProcessorIds, \Iterator $processors)
    {
        $processorIds = [];
        /** @var ProcessorMock $processor */
        foreach ($processors as $processor) {
            $processorIds[] = $processor->getProcessorId();
        }

        $this->assertEquals($expectedProcessorIds, $processorIds);
    }
}
