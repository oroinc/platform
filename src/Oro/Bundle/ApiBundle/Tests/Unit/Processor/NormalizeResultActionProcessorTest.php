<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Exception\NotSupportedConfigOperationException;
use Oro\Bundle\ApiBundle\Exception\UnhandledErrorsException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\NormalizeResultActionProcessor;
use Oro\Bundle\ApiBundle\Processor\NormalizeResultContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\SimpleProcessorFactory;
use Symfony\Component\Debug\BufferingLogger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class NormalizeResultActionProcessorTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ACTION = 'test';

    /** @var SimpleProcessorFactory */
    private $processorFactory;

    /** @var ProcessorBagConfigBuilder */
    private $processorBagConfigBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataProvider */
    private $metadataProvider;

    /** @var ProcessorBag */
    private $processorBag;

    /** @var NormalizeResultActionProcessor */
    private $processor;

    protected function setUp()
    {
        $this->processorFactory = new SimpleProcessorFactory();
        $this->processorBagConfigBuilder = new ProcessorBagConfigBuilder();
        $this->processorBag = new ProcessorBag($this->processorBagConfigBuilder, $this->processorFactory);
        $this->processorBagConfigBuilder->addGroup('group1', self::TEST_ACTION, -1);
        $this->processorBagConfigBuilder->addGroup('group2', self::TEST_ACTION, -2);
        $this->processorBagConfigBuilder->addGroup(
            NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP,
            self::TEST_ACTION,
            -3
        );

        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->processor = new NormalizeResultActionProcessor(
            $this->processorBag,
            self::TEST_ACTION
        );
    }

    /**
     * @return NormalizeResultContext
     */
    private function getContext()
    {
        $context = new NormalizeResultContext();
        $context->setAction(self::TEST_ACTION);
        $context->getRequestType()->add(RequestType::REST);
        $context->getRequestType()->add(RequestType::JSON_API);
        $context->setVersion('1.2');

        return $context;
    }

    /**
     * @return BufferingLogger
     */
    private function setLogger()
    {
        $logger = new BufferingLogger();
        $this->processor->setLogger($logger);

        return $logger;
    }

    /**
     * @param string $processorId
     * @param string $groupName
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|ProcessorInterface
     */
    private function addProcessor($processorId, $groupName)
    {
        $processor = $this->createMock(ProcessorInterface::class);
        $this->processorFactory->addProcessor($processorId, $processor);
        $this->processorBagConfigBuilder->addProcessor(
            $processorId,
            [],
            self::TEST_ACTION,
            $groupName
        );

        return $processor;
    }

    /**
     * @return array
     */
    public function loggerProvider()
    {
        return [
            [false],
            [true]
        ];
    }

    public function testWhenNoProcessors()
    {
        $context = $this->getContext();
        $this->processor->process($context);
    }

    public function testWhenNoExceptionsAndErrors()
    {
        $context = $this->getContext();

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));

        $this->processor->process($context);
    }

    /**
     * @param bool $withLogger
     *
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccurs($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

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
            ->with(self::identicalTo($context));

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
        if (null !== $logger) {
            self::assertEquals(
                [
                    [
                        'error',
                        'The execution of "processor1" processor is failed.',
                        [
                            'exception'   => $exception,
                            'action'      => self::TEST_ACTION,
                            'requestType' => 'rest,json_api',
                            'version'     => '1.2'
                        ]
                    ]
                ],
                $logger->cleanLogs()
            );
        }
    }

    /**
     * @param bool $withLogger
     *
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

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
            ->with(self::identicalTo($context));

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
        if (null !== $logger) {
            self::assertEquals(
                [
                    [
                        'info',
                        'An exception occurred in "processor1" processor.',
                        [
                            'exception'   => $exception,
                            'action'      => self::TEST_ACTION,
                            'requestType' => 'rest,json_api',
                            'version'     => '1.2'
                        ]
                    ]
                ],
                $logger->cleanLogs()
            );
        }
    }

    /**
     * @param bool $withLogger
     *
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccurs($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (NormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
        if (null !== $logger) {
            self::assertEquals(
                [
                    [
                        'info',
                        'Error(s) occurred in "processor1" processor.',
                        [
                            'errors'      => [['title' => 'some error']],
                            'action'      => self::TEST_ACTION,
                            'requestType' => 'rest,json_api',
                            'version'     => '1.2'
                        ]
                    ]
                ],
                $logger->cleanLogs()
            );
        }
    }

    /**
     * @param bool $withLogger
     *
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInLastProcessor($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor2->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (NormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
        if (null !== $logger) {
            self::assertEquals(
                [
                    [
                        'info',
                        'Error(s) occurred in "processor2" processor.',
                        [
                            'errors'      => [['title' => 'some error']],
                            'action'      => self::TEST_ACTION,
                            'requestType' => 'rest,json_api',
                            'version'     => '1.2'
                        ]
                    ]
                ],
                $logger->cleanLogs()
            );
        }
    }

    /**
     * @param bool $withLogger
     *
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (NormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
        if (null !== $logger) {
            self::assertEquals(
                [
                    [
                        'info',
                        'Error(s) occurred in "processor1" processor.',
                        [
                            'errors'      => [['title' => 'some error']],
                            'action'      => self::TEST_ACTION,
                            'requestType' => 'rest,json_api',
                            'version'     => '1.2'
                        ]
                    ]
                ],
                $logger->cleanLogs()
            );
        }
    }

    /**
     * @param bool $withLogger
     *
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInLastProcessorAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor2->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (NormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
        if (null !== $logger) {
            self::assertEquals(
                [
                    [
                        'info',
                        'Error(s) occurred in "processor2" processor.',
                        [
                            'errors'      => [['title' => 'some error']],
                            'action'      => self::TEST_ACTION,
                            'requestType' => 'rest,json_api',
                            'version'     => '1.2'
                        ]
                    ]
                ],
                $logger->cleanLogs()
            );
        }
    }

    /**
     * @param bool $withLogger
     *
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursAndNormalizeResultGroupIsDisabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        // disable "normalize_result" group
        $context->setLastGroup('group2');

        $exception = new \Exception('test exception');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::never())
            ->method('process');

        $this->expectException(get_class($exception));
        $this->expectExceptionMessage($exception->getMessage());

        $this->processor->process($context);

        if (null !== $logger) {
            self::assertEquals(
                [
                    [
                        'error',
                        'The execution of "processor1" processor is failed.',
                        [
                            'exception'   => $exception,
                            'action'      => self::TEST_ACTION,
                            'requestType' => 'rest,json_api',
                            'version'     => '1.2'
                        ]
                    ]
                ],
                $logger->cleanLogs()
            );
        }
    }

    /**
     * @param bool $withLogger
     *
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursAndNormalizeResultGroupIsDisabledAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);
        // disable "normalize_result" group
        $context->setLastGroup('group2');

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::never())
            ->method('process');

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
        if (null !== $logger) {
            self::assertEquals(
                [
                    [
                        'info',
                        'An exception occurred in "processor1" processor.',
                        [
                            'exception'   => $exception,
                            'action'      => self::TEST_ACTION,
                            'requestType' => 'rest,json_api',
                            'version'     => '1.2'
                        ]
                    ]
                ],
                $logger->cleanLogs()
            );
        }
    }

    /**
     * @param bool $withLogger
     *
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursAndNormalizeResultGroupIsDisabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        // disable "normalize_result" group
        $context->setLastGroup('group2');

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (NormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::never())
            ->method('process');

        try {
            $this->processor->process($context);
            self::fail(sprintf('Expected "%s" exception.', UnhandledErrorsException::class));
        } catch (UnhandledErrorsException $e) {
            $expected = new UnhandledErrorsException([$error]);
            self::assertEquals($expected, $e);
        }

        if (null !== $logger) {
            self::assertEquals(
                [
                    [
                        'info',
                        'Error(s) occurred in "processor1" processor.',
                        [
                            'errors'      => [['title' => 'some error']],
                            'action'      => self::TEST_ACTION,
                            'requestType' => 'rest,json_api',
                            'version'     => '1.2'
                        ]
                    ],
                    [
                        'error',
                        'Unhandled error(s) occurred in "processor1" processor.',
                        [
                            'errors'      => [['title' => 'some error']],
                            'action'      => self::TEST_ACTION,
                            'requestType' => 'rest,json_api',
                            'version'     => '1.2'
                        ]
                    ]
                ],
                $logger->cleanLogs()
            );
        }
    }

    /**
     * @param bool $withLogger
     *
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursAndNormalizeResultGroupIsDisabledAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);
        // disable "normalize_result" group
        $context->setLastGroup('group2');

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (NormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::never())
            ->method('process');

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
        if (null !== $logger) {
            self::assertEquals(
                [
                    [
                        'info',
                        'Error(s) occurred in "processor1" processor.',
                        [
                            'errors'      => [['title' => 'some error']],
                            'action'      => self::TEST_ACTION,
                            'requestType' => 'rest,json_api',
                            'version'     => '1.2'
                        ]
                    ]
                ],
                $logger->cleanLogs()
            );
        }
    }

    /**
     * @param bool $withLogger
     *
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursInNormalizeResultGroup($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();

        $exception = new \Exception('test exception');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $processor11 = $this->addProcessor('processor11', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor11->expects(self::never())
            ->method('process');

        $this->expectException(get_class($exception));
        $this->expectExceptionMessage($exception->getMessage());

        $this->processor->process($context);

        if (null !== $logger) {
            self::assertEquals(
                [
                    [
                        'error',
                        'The execution of "processor10" processor is failed.',
                        [
                            'exception'   => $exception,
                            'action'      => self::TEST_ACTION,
                            'requestType' => 'rest,json_api',
                            'version'     => '1.2'
                        ]
                    ]
                ],
                $logger->cleanLogs()
            );
        }
    }

    /**
     * @param bool $withLogger
     *
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursInNormalizeResultGroupAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $processor11 = $this->addProcessor('processor11', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor11->expects(self::never())
            ->method('process');

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
        if (null !== $logger) {
            self::assertEquals(
                [
                    [
                        'info',
                        'An exception occurred in "processor10" processor.',
                        [
                            'exception'   => $exception,
                            'action'      => self::TEST_ACTION,
                            'requestType' => 'rest,json_api',
                            'version'     => '1.2'
                        ]
                    ]
                ],
                $logger->cleanLogs()
            );
        }
    }

    /**
     * @param bool $withLogger
     *
     * @dataProvider loggerProvider
     */
    public function testWhenAuthenticationExceptionOccursInNormalizeResultGroup($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();

        $authenticationException = new AuthenticationException('Access Denied');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $processor11 = $this->addProcessor('processor11', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($authenticationException);
        $processor11->expects(self::never())
            ->method('process');

        try {
            $this->processor->process($context);
            self::fail(sprintf('The %s expected', get_class($authenticationException)));
        } catch (AuthenticationException $e) {
            self::assertEquals($authenticationException->getMessage(), $e->getMessage());
            if (null !== $logger) {
                self::assertEquals([], $logger->cleanLogs());
            }
        }
    }

    /**
     * @param bool $withLogger
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionAndThenAuthenticationExceptionOccursInNormalizeResultGroup($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();

        $exception = new \Exception('test exception');
        $authenticationException = new AuthenticationException('Access Denied');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $processor11 = $this->addProcessor('processor11', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($authenticationException);
        $processor11->expects(self::never())
            ->method('process');

        try {
            $this->processor->process($context);
            self::fail(sprintf('The %s expected', get_class($authenticationException)));
        } catch (AuthenticationException $e) {
            self::assertEquals($authenticationException->getMessage(), $e->getMessage());
            if (null !== $logger) {
                $logs = $logger->cleanLogs();
                self::assertCount(1, $logs);
                // remove log message context because here by some reasons PHPUnit hangs out
                // comparing two exception objects if them are not equal
                $loggedException = $logs[0][2]['exception'];
                unset($logs[0][2]);
                self::assertEquals(
                    [
                        ['error', 'The execution of "processor1" processor is failed.']
                    ],
                    $logs
                );
                self::assertInstanceOf(get_class($exception), $loggedException);
                self::assertEquals($exception->getMessage(), $loggedException->getMessage());
            }
        }
    }

    /**
     * @param bool $withLogger
     * @dataProvider loggerProvider
     */
    public function testWhenAuthenticationExceptionOccursInNormalizeResultGroupAndSoftErr($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $authenticationException = new AuthenticationException('Access Denied');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $processor11 = $this->addProcessor('processor11', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($authenticationException);
        $processor11->expects(self::never())
            ->method('process');

        try {
            $this->processor->process($context);
        } catch (AuthenticationException $e) {
            self::assertEquals($authenticationException->getMessage(), $e->getMessage());
            if (null !== $logger) {
                self::assertEquals([], $logger->cleanLogs());
            }
        }

        $errors = $context->getErrors();
        self::assertCount(1, $errors);
        $errorException = $errors[0]->getInnerException();
        self::assertInstanceOf(get_class($authenticationException), $errorException);
        self::assertEquals($authenticationException->getMessage(), $errorException->getMessage());
    }

    /**
     * @param bool $withLogger
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionAndThenAuthenticationExceptionOccursInNormalizeResultGroupAndSoftErr($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $exception = new \Exception('test exception');
        $authenticationException = new AuthenticationException('Access Denied');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $processor11 = $this->addProcessor('processor11', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($authenticationException);
        $processor11->expects(self::never())
            ->method('process');

        try {
            $this->processor->process($context);
        } catch (AuthenticationException $e) {
            self::assertEquals($authenticationException->getMessage(), $e->getMessage());
            if (null !== $logger) {
                $logs = $logger->cleanLogs();
                self::assertCount(1, $logs);
                // remove log message context because here by some reasons PHPUnit hangs out
                // comparing two exception objects if them are not equal
                $loggedException = $logs[0][2]['exception'];
                unset($logs[0][2]);
                self::assertEquals(
                    [
                        ['info', 'An exception occurred in "processor1" processor.']
                    ],
                    $logs
                );
                self::assertInstanceOf(get_class($exception), $loggedException);
                self::assertEquals($exception->getMessage(), $loggedException->getMessage());
            }
        }

        $errors = $context->getErrors();
        self::assertCount(1, $errors);
        $errorException = $errors[0]->getInnerException();
        self::assertInstanceOf(get_class($exception), $errorException);
        self::assertEquals($exception->getMessage(), $errorException->getMessage());
    }

    /**
     * @param bool $withLogger
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInNormalizeResultGroup($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $processor11 = $this->addProcessor('processor11', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (NormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor11->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
        if (null !== $logger) {
            self::assertEquals([], $logger->cleanLogs());
        }
    }

    /**
     * @param bool $withLogger
     *
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInNormalizeResultGroupAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $processor11 = $this->addProcessor('processor11', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (NormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor11->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
        if (null !== $logger) {
            self::assertEquals([], $logger->cleanLogs());
        }
    }

    /**
     * @param bool $withLogger
     *
     * @dataProvider loggerProvider
     */
    public function testWhenInternalPhpErrorOccurs($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();

        $internalPhpError = new \Error('test error', 1);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->will(new \PHPUnit\Framework\MockObject\Stub\Exception($internalPhpError));
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));

        $this->processor->process($context);

        $errors = $context->getErrors();
        self::assertCount(1, $errors);
        $errorException = $errors[0]->getInnerException();
        self::assertInstanceOf(\ErrorException::class, $errorException);
        self::assertSame($internalPhpError->getMessage(), $errorException->getMessage());
        self::assertSame($internalPhpError->getCode(), $errorException->getCode());
        self::assertSame($internalPhpError->getFile(), $errorException->getFile());
        self::assertSame($internalPhpError->getLine(), $errorException->getLine());

        if (null !== $logger) {
            $logs = $logger->cleanLogs();
            self::assertCount(1, $logs);
            unset($logs[0][2]);
            self::assertEquals(
                [
                    [
                        'error',
                        'The execution of "processor1" processor is failed.'
                    ]
                ],
                $logs
            );
        }
    }

    /**
     * @param \Exception $exception
     *
     * @dataProvider safeExceptionProvider
     */
    public function testWhenSafeExceptionOccurs(\Exception $exception)
    {
        $logger = $this->setLogger();

        $context = $this->getContext();

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

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
            ->with(self::identicalTo($context));

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
        self::assertEquals(
            [
                [
                    'info',
                    'An exception occurred in "processor1" processor.',
                    [
                        'exception'   => $exception,
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                ]
            ],
            $logger->cleanLogs()
        );
    }

    /**
     * @return array
     */
    public function safeExceptionProvider()
    {
        return [
            [new HttpException(Response::HTTP_BAD_REQUEST)],
            [new HttpException(Response::HTTP_METHOD_NOT_ALLOWED)],
            [new AccessDeniedException()],
            [new ForbiddenException('some reason')],
            [new NotSupportedConfigOperationException('Test\Class', 'test_operation')]
        ];
    }

    /**
     * @param HttpException $exception
     *
     * @dataProvider httpInternalServerErrorExceptionProvider
     */
    public function testWhenHttpInternalServerErrorExceptionOccurs(HttpException $exception)
    {
        $logger = $this->setLogger();

        $context = $this->getContext();

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

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
            ->with(self::identicalTo($context));

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
        self::assertEquals(
            [
                [
                    'error',
                    'The execution of "processor1" processor is failed.',
                    [
                        'exception'   => $exception,
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                ]
            ],
            $logger->cleanLogs()
        );
    }

    /**
     * @return array
     */
    public function httpInternalServerErrorExceptionProvider()
    {
        return [
            [new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR)],
            [new HttpException(Response::HTTP_NOT_IMPLEMENTED)]
        ];
    }

    /**
     * @param Error $error
     * @param array $loggedError
     *
     * @dataProvider errorForLogConversionProvider
     */
    public function testErrorForLogConversion(Error $error, array $loggedError)
    {
        $logger = $this->setLogger();
        $context = $this->getContext();

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(
                function (NormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
        if (null !== $logger) {
            self::assertEquals(
                [
                    [
                        'info',
                        'Error(s) occurred in "processor1" processor.',
                        [
                            'errors'      => [$loggedError],
                            'action'      => self::TEST_ACTION,
                            'requestType' => 'rest,json_api',
                            'version'     => '1.2'
                        ]
                    ]
                ],
                $logger->cleanLogs()
            );
        }
    }

    /**
     * @return array
     */
    public function errorForLogConversionProvider()
    {
        return [
            [
                Error::create('some error'),
                ['title' => 'some error']
            ],
            [
                Error::create(new Label('some error')),
                ['title' => 'some error']
            ],
            [
                Error::create(null, 'some error'),
                ['detail' => 'some error']
            ],
            [
                Error::create(null, new Label('some error')),
                ['detail' => 'some error']
            ],
            [
                Error::create(null)->setStatusCode(400),
                ['statusCode' => 400]
            ],
            [
                Error::create(null)->setCode('some_code'),
                ['code' => 'some_code']
            ],
            [
                Error::create(null)->setInnerException(new \Exception('some exception')),
                ['exception' => 'Exception: some exception']
            ],
            [
                Error::create(null)->setInnerException(
                    new NotSupportedConfigOperationException('Test\Class', 'test_operation')
                ),
                [
                    'exception' => NotSupportedConfigOperationException::class
                        . ': Requested unsupported operation "test_operation" when building config for "Test\Class".'
                ]
            ],
            [
                Error::create(null)->setSource(ErrorSource::createByParameter('parameter1')),
                ['source.parameter' => 'parameter1']
            ],
            [
                Error::create(null)->setSource(ErrorSource::createByPointer('pointer1')),
                ['source.pointer' => 'pointer1']
            ],
            [
                Error::create(null)->setSource(ErrorSource::createByPropertyPath('propertyPath1')),
                ['source.propertyPath' => 'propertyPath1']
            ]
        ];
    }
}
