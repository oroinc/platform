<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\SetResponseStatusCode;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SetResponseStatusCodeTest extends \PHPUnit_Framework_TestCase
{
    /** @var SetResponseStatusCode */
    protected $processor;

    public function setUp()
    {
        $this->processor = new SetResponseStatusCode();
    }

    /**
     * @dataProvider processData
     */
    public function testProcess(array $errors, $expectedCode)
    {
        $configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $context = new Context($configProvider, $metadataProvider);

        if (count($errors)) {
            foreach ($errors as $error) {
                $context->addError($error);
            }
        }

        $this->assertEquals(Response::HTTP_OK, $context->getResponseStatusCode());
        $this->processor->process($context);
        $this->assertEquals($expectedCode, $context->getResponseStatusCode());
    }

    public function processData()
    {
        $notAcceptableHttpError = new Error();
        $notAcceptableHttpError->setInnerException(new NotAcceptableHttpException());

        $conflictHttpError = new Error();
        $conflictHttpError->setInnerException(new ConflictHttpException());

        $accessDeniedException = new Error();
        $accessDeniedException->setInnerException(new AccessDeniedException());

        $nonHttpException = new Error();
        $nonHttpException->setInnerException(new \Exception());

        return [
            'context without errors' => [
                [],
                Response::HTTP_OK
            ],
            'context with one HTTP error' => [
                [$notAcceptableHttpError],
                Response::HTTP_NOT_ACCEPTABLE
            ],
            'context with multiply HTTP errors from one group' => [
                [$notAcceptableHttpError, $conflictHttpError],
                Response::HTTP_BAD_REQUEST
            ],
            'context with Symfony Security AccessDeniedException' => [
                [$accessDeniedException],
                Response::HTTP_FORBIDDEN
            ],
            'context with non HTTP error' => [
                [$nonHttpException],
                Response::HTTP_INTERNAL_SERVER_ERROR
            ],
            'context with multiply HTTP errors from different groups' => [
                [$notAcceptableHttpError, $conflictHttpError, $nonHttpException],
                Response::HTTP_INTERNAL_SERVER_ERROR
            ],
        ];
    }
}
