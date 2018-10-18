<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use FOS\RestBundle\Util\Codes;
use Oro\Bundle\TranslationBundle\Provider\OroTranslationAdapter;

class OroTranslationAdapterTest extends \PHPUnit\Framework\TestCase
{
    /** @var OroTranslationAdapter */
    protected $adapter;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $client;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $request;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $response;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $query;

    protected function setUp()
    {
        $this->client  = $this->createMock('Guzzle\Http\Client');
        $this->request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $this->response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $this->query = $this->createMock('Guzzle\Http\QueryString');

        $this->adapter = new OroTranslationAdapter($this->client);
        $this->adapter->setApiKey(uniqid());
    }

    public function testDownload()
    {
        $path = DIRECTORY_SEPARATOR . ltrim(uniqid(), DIRECTORY_SEPARATOR);

        $projects = ['Oro'];
        $package  = 'en';

        $this->client->expects($this->once())
            ->method('createRequest')
            ->will($this->returnValue($this->request));
        $this->request->expects($this->once())
            ->method('send')
            ->will($this->returnValue($this->response));
        $this->request->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($this->query));
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
        $code = Codes::HTTP_OK,
        $exceptionExpected = false
    ) {
        $this->client->expects($this->once())
            ->method('createRequest')
            ->will($this->returnValue($this->request));
        $this->request->expects($this->once())
            ->method('send')
            ->will($this->returnValue($this->response));
        $this->request->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($this->query));
        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->will($this->returnValue(200));
        $this->response->expects($this->once())
            ->method('json')
            ->will($this->returnValue($fetchedData));

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
        $correctStats = [
            [
                'code'              => 'en',
                'translationStatus' => 50,
                'lastBuildDate'     => '2014-04-01 12:00:00'
            ]
        ];

        return [
            'should throw exception if code is not OK'   => [
                [],
                [],
                400,
                '\RuntimeException'
            ],
            'if not json should throw exception'         => [
                [],
                'not JSON',
                Codes::HTTP_OK,
                '\RuntimeException'
            ],
            'not full filled stat should be skipped'     => [
                [],
                [['code' => 'en']],
                Codes::HTTP_OK,
                '\RuntimeException'
            ],
            'correct statistic should be returned as is' => [
                $correctStats,
                $correctStats
            ]
        ];
    }
}
