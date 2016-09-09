<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\SetHttpResponseStatusCode;

class SetHttpResponseStatusCodeTest extends \PHPUnit_Framework_TestCase
{
    /** @var SetHttpResponseStatusCode */
    protected $processor;

    public function setUp()
    {
        $this->processor = new SetHttpResponseStatusCode();
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(array $errors, $expectedCode)
    {
        $context = $this->getContext();

        foreach ($errors as $error) {
            $context->addError($error);
        }

        $this->processor->process($context);
        $this->assertEquals($expectedCode, $context->getResponseStatusCode());
    }

    public function processDataProvider()
    {
        return [
            'no errors' => [
                [],
                Response::HTTP_OK
            ],
            'one error without code' => [
                [
                    $this->getError()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            ],
            'one error' => [
                [
                    $this->getError(Response::HTTP_NOT_ACCEPTABLE)
                ],
                Response::HTTP_NOT_ACCEPTABLE
            ],
            'one error (parent code of a group)' => [
                [
                    $this->getError(Response::HTTP_BAD_REQUEST)
                ],
                Response::HTTP_BAD_REQUEST
            ],
            'several errors from one group' => [
                [
                    $this->getError(Response::HTTP_NOT_ACCEPTABLE),
                    $this->getError(Response::HTTP_CONFLICT)
                ],
                Response::HTTP_BAD_REQUEST
            ],
            'several errors with the same status code from one group' => [
                [
                    $this->getError(Response::HTTP_CONFLICT),
                    $this->getError(Response::HTTP_CONFLICT)
                ],
                Response::HTTP_CONFLICT
            ],
            'several errors from different groups (one error in a group with higher severity)' => [
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
            'several errors from different groups (one error in a group with lower severity)' => [
                [
                    $this->getError(Response::HTTP_NOT_ACCEPTABLE),
                    $this->getError(Response::HTTP_SERVICE_UNAVAILABLE),
                    $this->getError(Response::HTTP_NOT_IMPLEMENTED)
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            ],
            'several errors from different groups (one error in a group with lower severity), reverse' => [
                [
                    $this->getError(Response::HTTP_SERVICE_UNAVAILABLE),
                    $this->getError(Response::HTTP_NOT_IMPLEMENTED),
                    $this->getError(Response::HTTP_NOT_ACCEPTABLE)
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            ],
        ];
    }

    /**
     * @param int|null $statusCode
     *
     * @return Error
     */
    protected function getError($statusCode = null)
    {
        $error = new Error();
        $error->setStatusCode($statusCode);

        return $error;
    }

    /**
     * @return Context
     */
    protected function getContext()
    {
        $configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        return new Context($configProvider, $metadataProvider);
    }
}
