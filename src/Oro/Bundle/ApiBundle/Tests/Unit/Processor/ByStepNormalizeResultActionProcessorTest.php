<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ByStepNormalizeResultActionProcessor;
use Oro\Bundle\ApiBundle\Processor\ByStepNormalizeResultContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Oro\Component\ChainProcessor\SimpleProcessorFactory;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ByStepNormalizeResultActionProcessorTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ACTION = 'test';

    /** @var SimpleProcessorFactory */
    private $processorFactory;

    /** @var ProcessorBagConfigBuilder */
    private $processorBagConfigBuilder;

    /** @var ProcessorBag */
    private $processorBag;

    /** @var ByStepNormalizeResultActionProcessor */
    private $processor;

    protected function setUp()
    {
        $this->processorFactory = new SimpleProcessorFactory();
        $this->processorBagConfigBuilder = new ProcessorBagConfigBuilder();
        $this->processorBag = new ProcessorBag($this->processorBagConfigBuilder, $this->processorFactory);
        $this->processorBagConfigBuilder->addGroup('group1', self::TEST_ACTION, -1);
        $this->processorBagConfigBuilder->addGroup('group2', self::TEST_ACTION, -2);
        $this->processorBagConfigBuilder->addGroup(
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP,
            self::TEST_ACTION,
            -3
        );

        $this->processor = new ByStepNormalizeResultActionProcessor(
            $this->processorBag,
            self::TEST_ACTION
        );
    }

    /**
     * @return ByStepNormalizeResultContext
     */
    private function getContext()
    {
        $context = new ByStepNormalizeResultContext();
        $context->setAction(self::TEST_ACTION);
        $context->getRequestType()->add(RequestType::REST);
        $context->getRequestType()->add(RequestType::JSON_API);
        $context->setVersion('1.2');
        $context->setFirstGroup('group1');
        $context->setLastGroup('group1');

        return $context;
    }

    /**
     * @param string $processorId
     * @param string $groupName
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function addProcessor($processorId, $groupName)
    {
        $processor = $this->createMock('Oro\Component\ChainProcessor\ProcessorInterface');
        $this->processorFactory->addProcessor($processorId, $processor);
        $this->processorBagConfigBuilder->addProcessor(
            $processorId,
            [],
            self::TEST_ACTION,
            $groupName
        );

        return $processor;
    }

    public function loggerProvider()
    {
        return [
            [false],
            [true],
        ];
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Both the first and the last groups must be specified for the "test" action and these groups must be equal. First Group: "". Last Group: "".
     */
    // @codingStandardsIgnoreEnd
    public function testBothFirstAndLastGroupsAreNotSet()
    {
        $context = new ByStepNormalizeResultContext();
        $context->setAction(self::TEST_ACTION);
        $this->processor->process($context);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Both the first and the last groups must be specified for the "test" action and these groups must be equal. First Group: "". Last Group: "group1".
     */
    // @codingStandardsIgnoreEnd
    public function testFirstGroupIsNotSet()
    {
        $context = new ByStepNormalizeResultContext();
        $context->setAction(self::TEST_ACTION);
        $context->setLastGroup('group1');
        $this->processor->process($context);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Both the first and the last groups must be specified for the "test" action and these groups must be equal. First Group: "group1". Last Group: "".
     */
    // @codingStandardsIgnoreEnd
    public function testLastGroupIsNotSet()
    {
        $context = new ByStepNormalizeResultContext();
        $context->setAction(self::TEST_ACTION);
        $context->setFirstGroup('group1');
        $this->processor->process($context);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Both the first and the last groups must be specified for the "test" action and these groups must be equal. First Group: "group1". Last Group: "group2".
     */
    // @codingStandardsIgnoreEnd
    public function testFirstGroupIsNotEqualLastGroup()
    {
        $context = new ByStepNormalizeResultContext();
        $context->setAction(self::TEST_ACTION);
        $context->setFirstGroup('group1');
        $context->setLastGroup('group2');
        $this->processor->process($context);
    }

    public function testWhenNoProcessors()
    {
        $context = $this->getContext();
        $this->processor->process($context);
    }

    public function testShouldRemoveFailedGroupBeforeCallParentProcess()
    {
        $context = $this->getContext();
        $context->setFailedGroup('group2');

        $processor1 = $this->addProcessor('processor1', 'group1');

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) {
                    self::assertNull($context->getFailedGroup());
                }
            );

        $this->processor->process($context);
        self::assertNull($context->getFailedGroup());
    }

    public function testWhenNoExceptionsAndErrors()
    {
        $context = $this->getContext();

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor10->expects(self::never())
            ->method('process');

        $this->processor->process($context);
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccurs($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    self::assertEquals('group1', $context->getFailedGroup());
                }
            );

        if (null !== $logger) {
            $logger->expects(self::once())
                ->method('error')
                ->with(
                    'The execution of "processor1" processor is failed.',
                    [
                        'exception'   => $exception,
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
            $logger->expects(self::never())
                ->method('info');
        }

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    self::assertEquals('group1', $context->getFailedGroup());
                }
            );

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'An exception occurred in "processor1" processor.',
                    [
                        'exception'   => $exception,
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccurs($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    self::assertEquals('group1', $context->getFailedGroup());
                }
            );

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'Error(s) occurred in "processor1" processor.',
                    [
                        'errors'      => [['title' => $error->getTitle()]],
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInLastProcessor($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor2->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    self::assertEquals('group1', $context->getFailedGroup());
                }
            );

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'Error(s) occurred in "processor2" processor.',
                    [
                        'errors'      => [['title' => $error->getTitle()]],
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    self::assertEquals('group1', $context->getFailedGroup());
                }
            );

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'Error(s) occurred in "processor1" processor.',
                    [
                        'errors'      => [['title' => $error->getTitle()]],
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInLastProcessorAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor2->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    self::assertEquals('group1', $context->getFailedGroup());
                }
            );

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'Error(s) occurred in "processor2" processor.',
                    [
                        'errors'      => [['title' => $error->getTitle()]],
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
    }

    public function testWhenNoExceptionsAndErrorsAndHasInitialErrors()
    {
        $context = $this->getContext();
        $initialError = Error::create('initial error');
        $context->addError($initialError);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) use ($initialError) {
                self::assertEquals([$initialError], $context->getErrors());
            });
        $processor10->expects(self::never())
            ->method('process');

        $this->processor->process($context);

        self::assertEquals([$initialError], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursAndHasInitialErrors($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $initialError = Error::create('initial error');
        $context->addError($initialError);

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    self::assertEquals('group1', $context->getFailedGroup());
                }
            );

        if (null !== $logger) {
            $logger->expects(self::once())
                ->method('error')
                ->with(
                    'The execution of "processor1" processor is failed.',
                    [
                        'exception'   => $exception,
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
            $logger->expects(self::never())
                ->method('info');
        }

        $this->processor->process($context);

        self::assertEquals([$initialError, $error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursAndSoftErrorsHandlingEnabledAndHasInitialErrors($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $initialError = Error::create('initial error');
        $context->addError($initialError);
        $context->setSoftErrorsHandling(true);

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    self::assertEquals('group1', $context->getFailedGroup());
                }
            );

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'An exception occurred in "processor1" processor.',
                    [
                        'exception'   => $exception,
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$initialError, $error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursAndHasInitialErrors($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $initialError = Error::create('initial error');
        $context->addError($initialError);

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    self::assertEquals('group1', $context->getFailedGroup());
                }
            );

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'Error(s) occurred in "processor1" processor.',
                    [
                        'errors'      => [
                            ['title' => $initialError->getTitle()],
                            ['title' => $error->getTitle()]
                        ],
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$initialError, $error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInLastProcessorAndHasInitialErrors($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $initialError = Error::create('initial error');
        $context->addError($initialError);

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor2->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    self::assertEquals('group1', $context->getFailedGroup());
                }
            );

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'Error(s) occurred in "processor2" processor.',
                    [
                        'errors'      => [
                            ['title' => $initialError->getTitle()],
                            ['title' => $error->getTitle()]
                        ],
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$initialError, $error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursAndSoftErrorsHandlingEnabledAndHasInitialErrors($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $initialError = Error::create('initial error');
        $context->addError($initialError);
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    self::assertEquals('group1', $context->getFailedGroup());
                }
            );

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'Error(s) occurred in "processor1" processor.',
                    [
                        'errors'      => [
                            ['title' => $initialError->getTitle()],
                            ['title' => $error->getTitle()]
                        ],
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$initialError, $error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInLastProcessorAndSoftErrorsHandlingEnabledAndHasInitialErrors($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $initialError = Error::create('initial error');
        $context->addError($initialError);
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor2->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    self::assertEquals('group1', $context->getFailedGroup());
                }
            );

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'Error(s) occurred in "processor2" processor.',
                    [
                        'errors'      => [
                            ['title' => $initialError->getTitle()],
                            ['title' => $error->getTitle()]
                        ],
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$initialError, $error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursInNormalizeResultGroup($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setFirstGroup(ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $context->setLastGroup(ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $exception = new \Exception('test exception');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );
        $processor11 = $this->addProcessor(
            'processor11',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor11->expects(self::never())
            ->method('process');

        $this->expectException(get_class($exception));
        $this->expectExceptionMessage($exception->getMessage());

        if (null !== $logger) {
            $logger->expects(self::once())
                ->method('error')
                ->with(
                    'The execution of "processor10" processor is failed.',
                    [
                        'exception'   => $exception,
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
            $logger->expects(self::never())
                ->method('info');
        }

        $this->processor->process($context);
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursInNormalizeResultGroupAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setFirstGroup(ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $context->setLastGroup(ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $context->setSoftErrorsHandling(true);

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );
        $processor11 = $this->addProcessor(
            'processor11',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor11->expects(self::never())
            ->method('process');

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'An exception occurred in "processor10" processor.',
                    [
                        'exception'   => $exception,
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInNormalizeResultGroup($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setFirstGroup(ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $context->setLastGroup(ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );
        $processor11 = $this->addProcessor(
            'processor11',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor11->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::never())
                ->method('info');
        }

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInNormalizeResultGroupAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setFirstGroup(ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $context->setLastGroup(ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor(
            'processor10',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );
        $processor11 = $this->addProcessor(
            'processor11',
            ByStepNormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP
        );

        $processor1->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (ByStepNormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor11->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::never())
                ->method('info');
        }

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
    }
}
