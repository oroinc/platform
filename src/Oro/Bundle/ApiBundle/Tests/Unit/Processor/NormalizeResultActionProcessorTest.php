<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Exception\NotSupportedConfigOperationException;
use Oro\Bundle\ApiBundle\Exception\UnhandledErrorsException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\NormalizeResultActionProcessor;
use Oro\Bundle\ApiBundle\Processor\NormalizeResultContext;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;
use Oro\Component\Testing\Logger\BufferingLogger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NormalizeResultActionProcessorTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ACTION = 'test';

    /** @var ProcessorRegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $processorRegistry;

    /** @var ProcessorBagConfigBuilder */
    private $processorBagConfigBuilder;

    /** @var NormalizeResultActionProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->processorRegistry = $this->createMock(ProcessorRegistryInterface::class);
        $this->processorBagConfigBuilder = new ProcessorBagConfigBuilder();
        $this->processorBagConfigBuilder->addGroup('group1', self::TEST_ACTION, -1);
        $this->processorBagConfigBuilder->addGroup('group2', self::TEST_ACTION, -2);
        $this->processorBagConfigBuilder->addGroup(ApiActionGroup::NORMALIZE_RESULT, self::TEST_ACTION, -3);

        $this->processor = new NormalizeResultActionProcessor(
            new ProcessorBag($this->processorBagConfigBuilder, $this->processorRegistry),
            self::TEST_ACTION
        );
    }

    private function getContext(): NormalizeResultContext
    {
        $context = new NormalizeResultContext();
        $context->setAction(self::TEST_ACTION);
        $context->getRequestType()->add(RequestType::REST);
        $context->getRequestType()->add(RequestType::JSON_API);
        $context->setVersion('1.2');

        return $context;
    }

    private function setLogger(): BufferingLogger
    {
        $logger = new BufferingLogger();
        $this->processor->setLogger($logger);

        return $logger;
    }

    /**
     * @param array $processors [processorId => groupName, ...]
     *
     * @return ProcessorInterface[]|\PHPUnit\Framework\MockObject\MockObject[]
     */
    private function addProcessors(array $processors): array
    {
        $createdProcessors = [];
        $processorRegistryMap = [];
        foreach ($processors as $processorId => $groupName) {
            $this->processorBagConfigBuilder->addProcessor(
                $processorId,
                [],
                self::TEST_ACTION,
                $groupName
            );
            $processor = $this->createMock(ProcessorInterface::class);
            $createdProcessors[] = $processor;
            $processorRegistryMap[] = [$processorId, $processor];
        }
        $this->processorRegistry->expects(self::any())
            ->method('getProcessor')
            ->willReturnMap($processorRegistryMap);

        return $createdProcessors;
    }

    public function loggerProvider(): array
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

        [$processor1, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));

        $this->processor->process($context);
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccurs(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

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
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursAndSoftErrorsHandlingEnabled(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

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
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccurs(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();

        $error = Error::create('some error');

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (NormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
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
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInLastProcessor(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();

        $error = Error::create('some error');

        [$processor1, $processor2, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor2->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (NormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
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
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursAndSoftErrorsHandlingEnabled(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (NormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
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
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInLastProcessorAndSoftErrorsHandlingEnabled(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        [$processor1, $processor2, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor2->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (NormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
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
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursAndNormalizeResultGroupIsDisabled(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        // disable "normalize_result" group
        $context->setLastGroup('group2');

        $exception = new \Exception('test exception');

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

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
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursAndNormalizeResultGroupIsDisabledAndSoftErrHandlingEnabled(bool $withLogger)
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

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

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
                        'error',
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
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursAndNormalizeResultGroupIsDisabled(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        // disable "normalize_result" group
        $context->setLastGroup('group2');

        $error = Error::create('some error');

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (NormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
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
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursAndNormalizeResultGroupIsDisabledAndSoftErrorsHandlingEnabled(bool $withLogger)
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

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (NormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
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
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursInNormalizeResultGroup(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();

        $exception = new \Exception('test exception');

        [$processor1, $processor10, $processor11] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT,
            'processor11' => ApiActionGroup::NORMALIZE_RESULT
        ]);

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
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursInNormalizeResultGroupAndSoftErrorsHandlingEnabled(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        [$processor1, $processor10, $processor11] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT,
            'processor11' => ApiActionGroup::NORMALIZE_RESULT
        ]);

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
                        'error',
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
     * @dataProvider loggerProvider
     */
    public function testWhenAuthenticationExceptionOccursInNormalizeResultGroup(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();

        $authenticationException = new AuthenticationException('Access Denied');

        [$processor1, $processor10, $processor11] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT,
            'processor11' => ApiActionGroup::NORMALIZE_RESULT
        ]);

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
                self::assertEquals(
                    [
                        [
                            'info',
                            'An exception occurred in "processor10" processor.',
                            [
                                'exception'   => $authenticationException,
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
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionAndThenAuthExceptionOccursInNormalizeResultGroup(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();

        $exception = new \Exception('test exception');
        $authenticationException = new AuthenticationException('Access Denied');

        [$processor1, $processor10, $processor11] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT,
            'processor11' => ApiActionGroup::NORMALIZE_RESULT
        ]);

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
     * @dataProvider loggerProvider
     */
    public function testWhenAuthenticationExceptionOccursInNormalizeResultGroupAndSoftErr(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $authenticationException = new AuthenticationException('Access Denied');

        [$processor1, $processor10, $processor11] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT,
            'processor11' => ApiActionGroup::NORMALIZE_RESULT
        ]);

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
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionAndThenAuthExceptionOccursInNormalizeResultGroupAndSoftErr(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $exception = new \Exception('test exception');
        $authenticationException = new AuthenticationException('Access Denied');

        [$processor1, $processor10, $processor11] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT,
            'processor11' => ApiActionGroup::NORMALIZE_RESULT
        ]);

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
                        ['error', 'An exception occurred in "processor1" processor.']
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
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInNormalizeResultGroup(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();

        $error = Error::create('some error');

        [$processor1, $processor10, $processor11] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT,
            'processor11' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (NormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
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
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInNormalizeResultGroupAndSoftErrorsHandlingEnabled(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        [$processor1, $processor10, $processor11] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT,
            'processor11' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (NormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
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
     * @dataProvider loggerProvider
     */
    public function testWhenInternalPhpErrorOccurs(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->setLogger();
        }

        $context = $this->getContext();

        $internalPhpError = new \Error('test error', 1);

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($internalPhpError);
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
     * @dataProvider safeExceptionProvider
     */
    public function testWhenSafeExceptionOccurs(\Exception $exception)
    {
        $logger = $this->setLogger();

        $context = $this->getContext();

        $error = Error::createByException($exception);

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

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

    public function safeExceptionProvider(): array
    {
        return [
            [new HttpException(Response::HTTP_BAD_REQUEST)],
            [new HttpException(Response::HTTP_METHOD_NOT_ALLOWED)],
            [new AccessDeniedException()],
            [new AccessDeniedException('some reason')],
            [new NotSupportedConfigOperationException('Test\Class', 'test_operation')]
        ];
    }

    /**
     * @dataProvider httpInternalServerErrorExceptionProvider
     */
    public function testWhenHttpInternalServerErrorExceptionOccurs(HttpException $exception)
    {
        $logger = $this->setLogger();

        $context = $this->getContext();

        $error = Error::createByException($exception);

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

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

    public function httpInternalServerErrorExceptionProvider(): array
    {
        return [
            [new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR)],
            [new HttpException(Response::HTTP_NOT_IMPLEMENTED)]
        ];
    }

    /**
     * @dataProvider errorForLogConversionProvider
     */
    public function testErrorForLogConversion(Error $error, array $loggedError)
    {
        $logger = $this->setLogger();
        $context = $this->getContext();

        [$processor1] = $this->addProcessors([
            'processor1' => 'group1'
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (NormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });

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

    public function errorForLogConversionProvider(): array
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
