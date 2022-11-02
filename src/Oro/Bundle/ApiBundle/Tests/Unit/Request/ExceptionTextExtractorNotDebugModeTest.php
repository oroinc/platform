<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Exception\ActionNotAllowedException;
use Oro\Bundle\ApiBundle\Exception\NotSupportedConfigOperationException;
use Oro\Bundle\ApiBundle\Exception\ResourceNotAccessibleException;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Request\ExceptionTextExtractor;
use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExceptionTextExtractorNotDebugModeTest extends \PHPUnit\Framework\TestCase
{
    private ExceptionTextExtractor $exceptionTextExtractor;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->with(self::anything(), self::anything(), 'security')
            ->willReturnCallback(function ($label, $parameters) {
                $result = 'translated: ' . $label;
                if (!empty($parameters)) {
                    $result .= sprintf(' (%s)', implode(',', array_keys($parameters)));
                }

                return $result;
            });

        $this->exceptionTextExtractor = new ExceptionTextExtractor(
            false,
            $translator,
            [\UnexpectedValueException::class],
            [NotFoundExceptionInterface::class]
        );
    }

    private function createExecutionFailedException(
        \Exception $innerException = null,
        string $processorId = 'processor1'
    ): ExecutionFailedException {
        return new ExecutionFailedException(
            $processorId,
            null,
            null,
            $innerException
        );
    }

    /**
     * @dataProvider getExceptionStatusCodeDataProvider
     */
    public function testGetExceptionStatusCode(\Exception $exception, int $expectedStatusCode): void
    {
        self::assertSame(
            $expectedStatusCode,
            $this->exceptionTextExtractor->getExceptionStatusCode($exception)
        );
    }

    public function getExceptionStatusCodeDataProvider(): array
    {
        return [
            [new \UnexpectedValueException(), 500],
            [new BadRequestHttpException(), 400],
            [$this->createExecutionFailedException(new BadRequestHttpException()), 400],
            [new HttpException(400), 400],
            [new HttpException(401), 401],
            [new AccessDeniedException(), 403],
            [new \InvalidArgumentException(), 500],
            [new RuntimeException(), 500],
            [new ActionNotAllowedException(), 405],
            [new LockedException('Reason.'), 403],
            [new DisabledException('Reason.'), 403],
            [new UsernameNotFoundException('Reason.'), 403],
            [new ResourceNotAccessibleException(), 404],
            [new ServiceNotFoundException('test'), 500],
            [new NotSupportedConfigOperationException('Test\Class', 'test_operation'), 400]
        ];
    }

    public function testGetExceptionCode(): void
    {
        self::assertNull($this->exceptionTextExtractor->getExceptionCode(new \Exception()));
    }

    /**
     * @dataProvider getExceptionTypeDataProvider
     */
    public function testExceptionType(\Exception $exception, string $expectedType): void
    {
        self::assertEquals(
            $expectedType,
            $this->exceptionTextExtractor->getExceptionType($exception)
        );
    }

    public function getExceptionTypeDataProvider(): array
    {
        return [
            [new \Exception(), 'exception'],
            [new \UnexpectedValueException(), 'unexpected value exception'],
            [new \LogicException(), 'logic exception'],
            [new \InvalidArgumentException(), 'invalid argument exception'],
            [new BadRequestHttpException(), 'bad request http exception'],
            [$this->createExecutionFailedException(new BadRequestHttpException()), 'bad request http exception'],
            [new HttpException(400), 'bad request http exception'],
            [new HttpException(401), 'unauthorized http exception'],
            [new RuntimeException('Some error.'), 'runtime exception'],
            [new ActionNotAllowedException(), 'action not allowed exception'],
            [new AccessDeniedException('Reason.'), 'access denied exception'],
            [new AccessDeniedHttpException('Reason.'), 'access denied exception'],
            [new LockedException('Reason.'), 'authentication exception'],
            [new DisabledException('Reason.'), 'authentication exception'],
            [new UsernameNotFoundException('Reason.'), 'authentication exception'],
            [new ResourceNotAccessibleException(), 'resource not accessible exception'],
            [new ServiceNotFoundException('test'), 'service not found exception'],
            [
                new NotSupportedConfigOperationException('Test\Class', 'test_operation'),
                'not supported config operation exception'
            ]
        ];
    }

    /**
     * @dataProvider getExceptionTextDataProvider
     */
    public function testExceptionText(\Exception $exception, ?string $expectedText): void
    {
        self::assertSame(
            $expectedText,
            $this->exceptionTextExtractor->getExceptionText($exception)
        );
    }

    public function getExceptionTextDataProvider(): array
    {
        return [
            [
                new \Exception('some error'),
                null
            ],
            [
                new \Exception(),
                null
            ],
            [
                $this->createExecutionFailedException(new \Exception('some error')),
                null
            ],
            [
                $this->createExecutionFailedException(
                    $this->createExecutionFailedException(new \Exception('some error')),
                    'processor0'
                ),
                null
            ],
            [
                new \UnexpectedValueException('some error'),
                'some error.'
            ],
            [
                new \UnexpectedValueException(),
                null
            ],
            [
                $this->createExecutionFailedException(new \UnexpectedValueException('some error')),
                'some error. Processor: processor1.'
            ],
            [
                $this->createExecutionFailedException(
                    $this->createExecutionFailedException(new \UnexpectedValueException('some error')),
                    'processor0'
                ),
                'some error. Processor: processor0->processor1.'
            ],
            [
                new BadRequestHttpException('some error in request'),
                'some error in request.'
            ],
            [
                $this->createExecutionFailedException(new BadRequestHttpException('some error in request')),
                'some error in request. Processor: processor1.'
            ],
            [
                $this->createExecutionFailedException(
                    $this->createExecutionFailedException(new BadRequestHttpException('some error in request')),
                    'processor0'
                ),
                'some error in request. Processor: processor0->processor1.'
            ],
            [
                new RuntimeException('Some error.'),
                'Some error.'
            ],
            [
                new ActionNotAllowedException(),
                'The action is not allowed.'
            ],
            [
                new LockedException('Reason.'),
                'translated: Account is locked.'
            ],
            [
                new DisabledException('Reason.'),
                'translated: Account is disabled.'
            ],
            [
                new UsernameNotFoundException('Reason.'),
                'translated: Username could not be found. ({{ username }},{{ user_identifier }}).'
            ],
            [
                new ResourceNotAccessibleException(),
                'The resource is not accessible.'
            ],
            [
                new ServiceNotFoundException('test'),
                null
            ],
            [
                new NotSupportedConfigOperationException('Test\Class', 'test_operation'),
                'Requested unsupported operation "test_operation" when building config for "Test\Class".'
            ]
        ];
    }
}
