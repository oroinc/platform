<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Rest\Client\Guzzle;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestResponse;

class GuzzleRestResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $sourceResponse;

    /**
     * @var GuzzleRestResponse
     */
    protected $response;

    /**
     * @var string
     */
    protected $requestUrl = 'http://test';

    protected function setUp()
    {
        $this->sourceResponse = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $this->response = new GuzzleRestResponse($this->sourceResponse, $this->requestUrl);
    }

    public function testGetRequestUrl()
    {
        $this->assertEquals($this->requestUrl, $this->response->getRequestUrl());
    }

    /**
     * @dataProvider methodDelegationDataProvider
     */
    public function testMethodDelegationWorks(
        $targetMethod,
        array $targetArgs = array(),
        $sourceMethod = null,
        array $sourceArgs = null
    ) {
        if (!$sourceMethod) {
            $sourceMethod = $targetMethod;
        }
        if (null === $sourceArgs) {
            $sourceArgs = $targetArgs;
        }

        $stub = $this->sourceResponse->expects($this->once())
            ->method($sourceMethod);

        if ($sourceArgs) {
            $stub = call_user_func_array(array($stub, 'with'), $sourceArgs);
        }

        $expected = 'test';
        $stub->will($this->returnValue($expected));

        $this->assertEquals(
            $expected,
            call_user_func_array(array($this->response, $targetMethod), $targetArgs),
            $targetMethod
        );
    }

    public function methodDelegationDataProvider()
    {
        return array(
            array('__toString'),
            array('getBodyAsString', array(), 'getBody', array(true)),
            array('getStatusCode'),
            array('getMessage'),
            array('getHeader', array('Content-Type')),
            array('getHeaders'),
            array('getRawHeaders'),
            array('hasHeader', array('Content-Type')),
            array('getReasonPhrase'),
            array('getContentEncoding'),
            array('getContentLanguage'),
            array('getContentLength'),
            array('getContentLocation'),
            array('getContentDisposition'),
            array('getContentMd5'),
            array('getContentRange'),
            array('getContentType'),
            array('isContentType', array('application/json')),
            array('isClientError'),
            array('isInformational'),
            array('isRedirect'),
            array('isError'),
            array('isServerError'),
            array('isSuccessful'),
            array('json'),
            array('xml'),
            array('getRedirectCount'),
            array('getEffectiveUrl'),
        );
    }

    public function testGetSourceResponse()
    {
        $this->assertEquals($this->sourceResponse, $this->response->getSourceResponse());
    }
}
