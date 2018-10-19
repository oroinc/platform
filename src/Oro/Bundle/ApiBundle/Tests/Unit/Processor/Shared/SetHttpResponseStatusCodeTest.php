<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\SetHttpResponseStatusCode;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Symfony\Component\HttpFoundation\Response;

class SetHttpResponseStatusCodeTest extends GetListProcessorTestCase
{
    /** @var SetHttpResponseStatusCode */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new SetHttpResponseStatusCode();
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(array $errors, $expectedCode, $hasResult = true)
    {
        foreach ($errors as $error) {
            $this->context->addError($error);
        }

        if ($hasResult) {
            $this->context->setResult([]);
        }
        $this->processor->process($this->context);

        self::assertEquals($expectedCode, $this->context->getResponseStatusCode());
    }

    public function processDataProvider()
    {
        return [
            'no errors'                                                                                 => [
                [],
                Response::HTTP_OK
            ],
            'no errors, no result'                                                                      => [
                [],
                Response::HTTP_NO_CONTENT,
                false
            ],
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

    /**
     * @param int|null $statusCode
     *
     * @return Error
     */
    private function getError($statusCode = null)
    {
        $error = new Error();
        $error->setStatusCode($statusCode);

        return $error;
    }
}
