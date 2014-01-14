<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use FOS\Rest\Util\Codes;
use Oro\Bundle\TranslationBundle\Provider\OroTranslationAdapter;

class OroTranslationAdapterTest extends \PHPUnit_Framework_TestCase
{
    /** @var OroTranslationAdapter */
    protected $adapter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $apiRequestMock;

    public function setUp()
    {
        $endpoint = 'http://localhost/test';
        $key      = uniqid();

        $this->apiRequestMock = $this->getMock('Oro\Bundle\TranslationBundle\Provider\ApiRequestInterface');
        $this->adapter        = new OroTranslationAdapter($this->apiRequestMock, $endpoint, $key);
    }

    public function testDownload()
    {
        $path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . ltrim(uniqid(), DIRECTORY_SEPARATOR);

        $projects = ['Oro'];
        $package  = 'en';

        $this->apiRequestMock->expects($this->once())
            ->method('reset');
        $this->apiRequestMock->expects($this->once())
            ->method('setOptions');
        $this->apiRequestMock->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(true));

        $result = $this->adapter->download($path, $projects, $package);

        $this->assertTrue($result);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testParseResponse()
    {
        $test     = ['test' => 1];
        $response = json_encode($test);
        $result   = $this->adapter->parseResponse($response);

        $this->assertEquals((object)$test, $result);

        // test with exception
        $test     = [
            'message' => 'error',
            'code'    => 1,
        ];
        $response = json_encode($test);
        $this->adapter->parseResponse($response);
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
        $this->apiRequestMock->expects($this->once())
            ->method('reset');

        $this->apiRequestMock->expects($this->once())
            ->method('setOptions');

        $this->apiRequestMock->expects($this->once())
            ->method('execute')->will($this->returnValue($fetchedData));

        $this->apiRequestMock->expects($this->once())
            ->method('getResponseCode')->will($this->returnValue($code));

        if ($exceptionExpected) {
            $this->setExpectedException($exceptionExpected);
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
                json_encode([['code' => 'en']]),
                Codes::HTTP_OK,
                '\RuntimeException'
            ],
            'correct statistic should be returned as is' => [
                $correctStats,
                json_encode($correctStats)
            ]
        ];
    }
}
