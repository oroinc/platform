<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Utils;
use Oro\Bundle\TranslationBundle\Provider\OroTranslationAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class OroTranslationAdapterTest extends TestCase
{
    /** @var OroTranslationAdapter */
    protected $adapter;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $client;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $request;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $response;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapter = new OroTranslationAdapter($this->client);
        $this->adapter->setApiKey(uniqid());
    }

    public function testDownload()
    {
        $path = DIRECTORY_SEPARATOR . ltrim(uniqid(), DIRECTORY_SEPARATOR);

        $projects = ['Oro'];
        $package  = 'en';

        $this->client->expects($this->once())
            ->method('send')
            ->will($this->returnValue($this->response));
        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $result = $this->adapter->download($path, $projects, $package);

        $this->assertTrue($result);
    }

    /**
     * @dataProvider statisticProvider
     *
     * @param array $expectedResult
     * @param array $fetchedData
     * @param int   $code
     * @param bool  $exceptionExpected
     */
    public function testFetchStatistic(
        $expectedResult,
        $fetchedData,
        $code = 200,
        $exceptionExpected = false
    ) {
        $this->client->expects($this->once())
            ->method('send')
            ->will($this->returnValue($this->response));
        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->will($this->returnValue(200));
        $body = $this->createMock(StreamInterface::class);
        $body->expects($this->once())
            ->method('getContents')
            ->willReturn($fetchedData);
        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        if ($exceptionExpected) {
            $this->expectException($exceptionExpected);
        }

        $result = $this->adapter->fetchStatistic();
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function statisticProvider()
    {
        return [
            'should throw exception if code is not OK' => [
                'expectedResult' => [],
                'fetchedData' => Utils::jsonEncode([], true),
                'code' => 400,
                'exceptionExpected' => '\RuntimeException',
            ],
            'if not json should throw exception' => [
                'expectedResult' => [],
                'fetchedData' => 'not JSON',
                'code' => 200,
                'exceptionExpected' => InvalidArgumentException::class,
            ],
            'not full filled stat should be skipped' => [
                'expectedResult' => [],
                'fetchedData' => Utils::jsonEncode([['code' => 'en']]),
                'code' => 200,
                'exceptionExpected' => '\RuntimeException',
            ],
            'correct statistic should be returned as is' => [
                'expectedResult' => [
                    [
                        'code' => 'en',
                        'translationStatus' => 50,
                        'lastBuildDate' => '2014-04-01 12:00:00',
                    ],
                ],
                'fetchedData' => Utils::jsonEncode(
                    [
                        [
                            'code' => 'en',
                            'translationStatus' => 50,
                            'lastBuildDate' => '2014-04-01 12:00:00',
                        ],
                    ],
                    true
                ),
            ],
        ];
    }
}
