<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\SetHttpResponseStatusCode;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Symfony\Component\HttpFoundation\Response;

class SetHttpResponseStatusCodeTest extends GetListProcessorTestCase
{
    private SetHttpResponseStatusCode $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SetHttpResponseStatusCode();
    }

    public function testProcessWhenResponseStatusCodeAlreadySet(): void
    {
        $this->context->setResponseStatusCode(Response::HTTP_ACCEPTED);
        $this->context->setResult([]);
        $this->processor->process($this->context);

        self::assertEquals(Response::HTTP_ACCEPTED, $this->context->getResponseStatusCode());
    }

    public function testProcessWhenNoErrors(): void
    {
        $this->context->setResult([]);
        $this->processor->process($this->context);

        self::assertEquals(Response::HTTP_OK, $this->context->getResponseStatusCode());
    }

    public function testProcessWhenNoErrorsAndNoResult(): void
    {
        $this->processor->process($this->context);

        self::assertEquals(Response::HTTP_NO_CONTENT, $this->context->getResponseStatusCode());
    }

    /**
     * @dataProvider processHasErrorsDataProvider
     */
    public function testProcessHasErrors(array $errors, int $expectedCode): void
    {
        foreach ($errors as $error) {
            $this->context->addError($error);
        }

        $this->context->setResult([]);
        $this->processor->process($this->context);

        self::assertEquals($expectedCode, $this->context->getResponseStatusCode());
    }

    public function processHasErrorsDataProvider(): array
    {
        return [
            'one error without code'                                                                    => [
                [
                    $this->getError()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            ],
            'one error'                                                                                 => [
                [
                    $this->getError(Response::HTTP_NOT_ACCEPTABLE)
                ],
                Response::HTTP_NOT_ACCEPTABLE
            ],
            'one error (parent code of a group)'                                                        => [
                [
                    $this->getError(Response::HTTP_BAD_REQUEST)
                ],
                Response::HTTP_BAD_REQUEST
            ],
            'several errors from one group'                                                             => [
                [
                    $this->getError(Response::HTTP_NOT_ACCEPTABLE),
                    $this->getError(Response::HTTP_CONFLICT)
                ],
                Response::HTTP_BAD_REQUEST
            ],
            'several errors with the same status code from one group'                                   => [
                [
                    $this->getError(Response::HTTP_CONFLICT),
                    $this->getError(Response::HTTP_CONFLICT)
                ],
                Response::HTTP_CONFLICT
            ],
            'several errors from different groups (one error in a group with higher severity)'          => [
                [
                    $this->getError(Response::HTTP_NOT_ACCEPTABLE),
                    $this->getError(Response::HTTP_CONFLICT),
                    $this->getError(Response::HTTP_NOT_IMPLEMENTED)
                ],
                Response::HTTP_NOT_IMPLEMENTED
            ],
            'several errors from different groups (one error in a group with higher severity), reverse' => [
                [
                    $this->getError(Response::HTTP_NOT_IMPLEMENTED),
                    $this->getError(Response::HTTP_NOT_ACCEPTABLE),
                    $this->getError(Response::HTTP_CONFLICT)
                ],
                Response::HTTP_NOT_IMPLEMENTED
            ],
            'several errors from different groups (one error in a group with lower severity)'           => [
                [
                    $this->getError(Response::HTTP_NOT_ACCEPTABLE),
                    $this->getError(Response::HTTP_SERVICE_UNAVAILABLE),
                    $this->getError(Response::HTTP_NOT_IMPLEMENTED)
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            ],
            'several errors from different groups (one error in a group with lower severity), reverse'  => [
                [
                    $this->getError(Response::HTTP_SERVICE_UNAVAILABLE),
                    $this->getError(Response::HTTP_NOT_IMPLEMENTED),
                    $this->getError(Response::HTTP_NOT_ACCEPTABLE)
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            ]
        ];
    }

    private function getError(int $statusCode = null): Error
    {
        $error = new Error();
        $error->setStatusCode($statusCode);

        return $error;
    }
}
