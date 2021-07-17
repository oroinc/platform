<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Rest\Client\Guzzle;

use GuzzleHttp\Psr7\Response;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestResponse;
use PHPUnit\Framework\TestCase;

class GuzzleRestResponseTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Response
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

    protected function setUp(): void
    {
        $this->sourceResponse = $this->getMockBuilder(Response::class)
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
     * @param            $targetMethod
     * @param array      $targetArgs
     * @param null       $sourceMethod
     * @param array|null $sourceArgs
     */
    public function testMethodDelegationWorks(
        $targetMethod,
        array $targetArgs = [],
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
            $stub = call_user_func_array([$stub, 'with'], $sourceArgs);
        }

        $expected = 'test';
        $stub->will($this->returnValue($expected));

        $this->assertEquals(
            $expected,
            call_user_func_array([$this->response, $targetMethod], $targetArgs),
            $targetMethod
        );
    }

    public function methodDelegationDataProvider(): array
    {
        return [
            ['getBodyAsString', [], 'getBody'],
            ['getStatusCode'],
            ['getHeader', ['Content-Type']],
            ['getHeaders'],
            ['hasHeader', ['Content-Type']],
            ['getReasonPhrase'],
        ];
    }

    public function testGetSourceResponse()
    {
        $this->assertEquals($this->sourceResponse, $this->response->getSourceResponse());
    }
}
