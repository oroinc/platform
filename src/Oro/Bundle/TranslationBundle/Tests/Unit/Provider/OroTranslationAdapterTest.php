<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

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
        $this->adapter = new OroTranslationAdapter($this->apiRequestMock, $endpoint, $key);
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
        $test = ['test' => 1];
        $response = json_encode($test);
        $result = $this->adapter->parseResponse($response);

        $this->assertEquals((object)$test, $result);

        // test with exception
        $test = [
            'message' => 'error',
            'code'    => 1,
        ];
        $response = json_encode($test);
        $this->adapter->parseResponse($response);
    }
}
