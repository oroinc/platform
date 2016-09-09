<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\MatchApplicableChecker;
use Oro\Component\ChainProcessor\ProcessorFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorIterator;

class MatchApplicableCheckerTest extends \PHPUnit_Framework_TestCase
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
                'processor'  => 'processor1',
                'attributes' => []
            ],
            [
                'processor'  => 'processor2',
                'attributes' => ['class' => 'TestCls']
            ],
            [
                'processor'  => 'processor3',
                'attributes' => ['type' => 'test']
            ],
            [
                'processor'  => 'processor4',
                'attributes' => ['class' => 'TestCls', 'type' => 'test']
            ],
            [
                'processor'  => 'processor5',
                'attributes' => ['class' => 'TestCls', 'type' => 'test', 'another' => 'val']
            ],
            [
                'processor'  => 'processor6',
                'attributes' => ['class' => 'AnotherCls']
            ],
            [
                'processor'  => 'processor7',
                'attributes' => ['type' => 'test']
            ],
            [
                'processor'  => 'processor8',
                'attributes' => ['class' => 'AnotherCls', 'type' => 'test']
            ],
            [
                'processor'  => 'processor9',
                'attributes' => ['class' => 'AnotherCls', 'type' => 'test', 'another' => 'val']
            ],
            [
                'processor'  => 'processor10',
                'attributes' => ['class' => 'TestCls']
            ],
            [
                'processor'  => 'processor11',
                'attributes' => ['type' => 'another']
            ],
            [
                'processor'  => 'processor12',
                'attributes' => ['class' => 'TestCls', 'type' => 'another']
            ],
            [
                'processor'  => 'processor13',
                'attributes' => ['class' => 'TestCls', 'type' => 'another', 'another' => 'val']
            ],
            [
                'processor'  => 'processor14',
                'attributes' => ['class' => 'TestCls', 'feature' => 'feature1']
            ],
            [
                'processor'  => 'processor15',
                'attributes' => ['class' => 'TestCls', 'feature' => 'feature2']
            ],
            [
                'processor'  => 'processor16',
                'attributes' => ['class' => 'TestCls', 'feature' => 'feature3']
            ],
            [
                'processor'  => 'processor17',
                'attributes' => ['class' => 'TestCls', 'feature' => ['&' => ['feature1', 'feature3']]]
            ],
            [
                'processor'  => 'processor18',
                'attributes' => ['class' => 'TestCls', 'feature' => ['&' => ['feature3', 'feature1']]]
            ],
            [
                'processor'  => 'processor19',
                'attributes' => ['class' => 'TestCls', 'feature' => ['&' => ['feature1', 'feature2']]]
            ],
            [
                'processor'  => 'processor20',
                'attributes' => ['type' => ['!' => 'test']]
            ],
            [
                'processor'  => 'processor21',
                'attributes' => ['type' => ['!' => 'test1']]
            ],
            [
                'processor'  => 'processor22',
                'attributes' => ['feature' => '!feature1']
            ],
            [
                'processor'  => 'processor23',
                'attributes' => ['feature' => ['!' => 'feature2']]
            ],
            [
                'processor'  => 'processor24',
                'attributes' => ['feature' => ['&' => [['!' => 'feature1'], ['!' => 'feature2']]]]
            ],
            [
                'processor'  => 'processor25',
                'attributes' => ['feature' => ['&' => ['feature1', ['!' => 'feature2']]]]
            ],
            [
                'processor'  => 'processor26',
                'attributes' => ['feature' => ['&' => [['!' => 'feature1'], 'feature2']]]
            ],
            [
                'processor'  => 'processor27',
                'attributes' => ['feature' => ['&' => [['!' => 'feature1'], ['!' => 'feature3']]]]
            ],
            [
                'processor'  => 'processor28',
                'attributes' => ['feature' => ['&' => [['!' => 'feature2'], ['!' => 'feature4']]]]
            ],
            [
                'processor'  => 'processor29',
                'attributes' => ['type' => ['&' => [['!' => 'test'], ['!' => 'test1']]]]
            ],
            [
                'processor'  => 'processor30',
                'attributes' => ['type' => ['&' => ['test', ['!' => 'test1']]]]
            ],
            [
                'processor'  => 'processor31',
                'attributes' => ['type' => ['&' => [['!' => 'test'], 'test1']]]
            ],
            [
                'processor'  => 'processor32',
                'attributes' => ['type' => ['&' => ['test', 'test1']]]
            ],
            [
                'processor'  => 'processor33',
                'attributes' => ['class' => 'TestCls', 'featureObj' => ['&' => ['feature1', 'feature3']]]
            ],
            [
                'processor'  => 'processor34',
                'attributes' => ['class' => 'TestCls', 'featureObj' => ['&' => ['feature3', 'feature1']]]
            ],
            [
                'processor'  => 'processor35',
                'attributes' => ['class' => 'TestCls', 'featureObj' => ['&' => ['feature1', 'feature2']]]
            ],
            [
                'processor'  => 'processor36',
                'attributes' => ['feature' => ['|' => [['!' => 'feature1'], ['!' => 'feature2']]]]
            ],
            [
                'processor'  => 'processor37',
                'attributes' => ['feature' => ['|' => ['feature1', ['!' => 'feature2']]]]
            ],
            [
                'processor'  => 'processor38',
                'attributes' => ['feature' => ['|' => [['!' => 'feature1'], 'feature2']]]
            ],
            [
                'processor'  => 'processor39',
                'attributes' => ['feature' => ['|' => [['!' => 'feature1'], ['!' => 'feature3']]]]
            ],
            [
                'processor'  => 'processor40',
                'attributes' => ['feature' => ['|' => [['!' => 'feature2'], ['!' => 'feature4']]]]
            ],
            [
                'processor'  => 'processor41',
                'attributes' => ['type' => ['|' => [['!' => 'test'], ['!' => 'test1']]]]
            ],
            [
                'processor'  => 'processor42',
                'attributes' => ['type' => ['|' => ['test', ['!' => 'test1']]]]
            ],
            [
                'processor'  => 'processor43',
                'attributes' => ['type' => ['|' => [['!' => 'test'], 'test1']]]
            ],
            [
                'processor'  => 'processor44',
                'attributes' => ['type' => ['|' => ['test', 'test1']]]
            ],
            [
                'processor'  => 'processor45',
                'attributes' => ['class' => 'TestCls', 'featureObj' => ['|' => ['feature1', 'feature3']]]
            ],
            [
                'processor'  => 'processor46',
                'attributes' => ['class' => 'TestCls', 'featureObj' => ['|' => ['feature3', 'feature1']]]
            ],
            [
                'processor'  => 'processor47',
                'attributes' => ['class' => 'TestCls', 'featureObj' => ['|' => ['feature1', 'feature2']]]
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
                'processor'  => 'processor1',
                'attributes' => []
            ],
            [
                'processor'  => 'processor1_disabled',
                'attributes' => ['disabled' => true]
            ],
            [
                'processor'  => 'processor2',
                'attributes' => ['class' => 'TestCls']
            ],
            [
                'processor'  => 'processor2_disabled',
                'attributes' => ['disabled' => true, 'class' => 'TestCls']
            ],
            [
                'processor'  => 'processor3',
                'attributes' => ['type' => 'test']
            ],
            [
                'processor'  => 'processor3_disabled',
                'attributes' => ['disabled' => true, 'type' => 'test']
            ],
            [
                'processor'  => 'processor4',
                'attributes' => ['class' => 'TestCls', 'type' => 'test']
            ],
            [
                'processor'  => 'processor4_disabled',
                'attributes' => ['disabled' => true, 'class' => 'TestCls', 'type' => 'test']
            ],
            [
                'processor'  => 'processor5',
                'attributes' => ['class' => 'TestCls', 'type' => 'test', 'another' => 'val']
            ],
            [
                'processor'  => 'processor5_disabled',
                'attributes' => ['disabled' => true, 'class' => 'TestCls', 'type' => 'test', 'another' => 'val']
            ],
            [
                'processor'  => 'processor6',
                'attributes' => ['class' => 'AnotherCls']
            ],
            [
                'processor'  => 'processor6_disabled',
                'attributes' => ['disabled' => true, 'class' => 'AnotherCls']
            ],
            [
                'processor'  => 'processor7',
                'attributes' => ['type' => 'test']
            ],
            [
                'processor'  => 'processor7_disabled',
                'attributes' => ['disabled' => true, 'type' => 'test']
            ],
            [
                'processor'  => 'processor8',
                'attributes' => ['class' => 'AnotherCls', 'type' => 'test']
            ],
            [
                'processor'  => 'processor8_disabled',
                'attributes' => ['disabled' => true, 'class' => 'AnotherCls', 'type' => 'test']
            ],
            [
                'processor'  => 'processor9',
                'attributes' => ['class' => 'AnotherCls', 'type' => 'test', 'another' => 'val']
            ],
            [
                'processor'  => 'processor9_disabled',
                'attributes' => ['disabled' => true, 'class' => 'AnotherCls', 'type' => 'test', 'another' => 'val']
            ],
            [
                'processor'  => 'processor10',
                'attributes' => ['class' => 'TestCls']
            ],
            [
                'processor'  => 'processor10_disabled',
                'attributes' => ['disabled' => true, 'class' => 'TestCls']
            ],
            [
                'processor'  => 'processor11',
                'attributes' => ['type' => 'another']
            ],
            [
                'processor'  => 'processor11_disabled',
                'attributes' => ['disabled' => true, 'type' => 'another']
            ],
            [
                'processor'  => 'processor12',
                'attributes' => ['class' => 'TestCls', 'type' => 'another']
            ],
            [
                'processor'  => 'processor12_disabled',
                'attributes' => ['disabled' => true, 'class' => 'TestCls', 'type' => 'another']
            ],
            [
                'processor'  => 'processor13',
                'attributes' => ['class' => 'TestCls', 'type' => 'another', 'another' => 'val']
            ],
            [
                'processor'  => 'processor13_disabled',
                'attributes' => ['disabled' => true, 'class' => 'TestCls', 'type' => 'another', 'another' => 'val']
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
        $factory = $this->getMock('Oro\Component\ChainProcessor\ProcessorFactoryInterface');
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
