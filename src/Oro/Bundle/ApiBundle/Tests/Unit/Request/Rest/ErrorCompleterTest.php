<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\Rest;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\ErrorTitleOverrideProvider;
use Oro\Bundle\ApiBundle\Request\ExceptionTextExtractorInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Rest\ErrorCompleter;
use Symfony\Component\HttpFoundation\Response;

class ErrorCompleterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ExceptionTextExtractorInterface */
    private $exceptionTextExtractor;

    /** @var RequestType */
    private $requestType;

    /** @var ErrorCompleter */
    private $errorCompleter;

    protected function setUp(): void
    {
        $this->exceptionTextExtractor = $this->createMock(ExceptionTextExtractorInterface::class);
        $this->requestType = new RequestType([RequestType::REST]);

        $this->errorCompleter = new ErrorCompleter(
            new ErrorTitleOverrideProvider(['test title alias' => 'test title']),
            $this->exceptionTextExtractor
        );
    }

    public function testCompleteErrorWhenErrorTitleHasSubstitution()
    {
        $error = new Error();
        $error->setStatusCode(400);
        $error->setTitle('test title alias');
        $error->setDetail('test detail');

        $expectedError = new Error();
        $expectedError->setStatusCode(400);
        $expectedError->setTitle('test title');
        $expectedError->setDetail('test detail');

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithoutInnerException()
    {
        $error = new Error();
        $expectedError = new Error();

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithInnerExceptionAndAlreadyCompletedProperties()
    {
        $exception = new \Exception('some exception');

        $error = new Error();
        $error->setStatusCode(400);
        $error->setCode('test code');
        $error->setTitle('test title');
        $error->setDetail('test detail');
        $error->setInnerException($exception);

        $expectedError = new Error();
        $expectedError->setStatusCode(400);
        $expectedError->setCode('test code');
        $expectedError->setTitle('test title');
        $expectedError->setDetail('test detail');
        $expectedError->setInnerException($exception);

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithInnerExceptionAndExceptionTextExtractorReturnsNothing()
    {
        $exception = new \Exception('some exception');

        $error = new Error();
        $error->setInnerException($exception);

        $expectedError = new Error();
        $expectedError->setInnerException($exception);

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithInnerException()
    {
        $exception = new \Exception('some exception');

        $error = new Error();
        $error->setInnerException($exception);

        $expectedError = new Error();
        $expectedError->setStatusCode(500);
        $expectedError->setCode('test code');
        $expectedError->setTitle('test title');
        $expectedError->setDetail('test detail');
        $expectedError->setInnerException($exception);

        $this->exceptionTextExtractor->expects(self::once())
            ->method('getExceptionStatusCode')
            ->with(self::identicalTo($exception))
            ->willReturn($expectedError->getStatusCode());
        $this->exceptionTextExtractor->expects(self::once())
            ->method('getExceptionCode')
            ->with(self::identicalTo($exception))
            ->willReturn($expectedError->getCode());
        $this->exceptionTextExtractor->expects(self::once())
            ->method('getExceptionType')
            ->with(self::identicalTo($exception))
            ->willReturn($expectedError->getTitle());
        $this->exceptionTextExtractor->expects(self::once())
            ->method('getExceptionText')
            ->with(self::identicalTo($exception))
            ->willReturn($expectedError->getDetail());

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorTitleByStatusCode()
    {
        $error = new Error();
        $error->setStatusCode(400);

        $expectedError = new Error();
        $expectedError->setStatusCode(400);
        $expectedError->setTitle(strtolower(Response::$statusTexts[400]));

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function testCompleteErrorTitleByUnknownStatusCode()
    {
        $error = new Error();
        $error->setStatusCode(1000);

        $expectedError = new Error();
        $expectedError->setStatusCode(1000);

        $this->errorCompleter->complete($error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }

    public function testFixIncludedEntityPath()
    {
        $error = new Error();
        $error->setSource(ErrorSource::createByPropertyPath('association1.field1'));

        $expectedError = clone $error;
        $expectedError->setSource(clone $error->getSource());

        $this->errorCompleter->fixIncludedEntityPath('/included/0', $error, $this->requestType);
        self::assertEquals($expectedError, $error);
    }
}
