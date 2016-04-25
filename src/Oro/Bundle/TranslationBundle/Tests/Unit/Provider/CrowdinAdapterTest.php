<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Provider\CrowdinAdapter;

class CrowdinAdapterTest extends \PHPUnit_Framework_TestCase
{
    /** @var CrowdinAdapter */
    protected $adapter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $client;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $response;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $query;

    protected function setUp()
    {
        $this->logger = $this->getMock('Psr\Log\LoggerInterface');
        $this->client = $this->getMock('Guzzle\Http\Client');
        $this->request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $this->response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $this->query = $this->getMock('Guzzle\Http\QueryString');

        $this->adapter = new CrowdinAdapter($this->client);
        $this->adapter->setApiKey('some-api-key');
        $this->adapter->setLogger($this->logger);
    }

    protected function tearDown()
    {
        unset($this->logger, $this->client, $this->adapter);
    }

    /**
     * Test upload method
     */
    public function testUpload()
    {
        $mode = 'add';
        $separator = DIRECTORY_SEPARATOR;
        $files = [
            "some{$separator}path{$separator}to{$separator}file.yml" => "api{$separator}path{$separator}test.yml",
        ];

        $this->adapter->setProjectId(1);

        $this->client->expects($this->exactly(4))
            ->method('createRequest')
            ->will($this->returnValue($this->request));
        $this->request->expects($this->exactly(4))
            ->method('send')
            ->will($this->returnValue($this->response));
        $this->request->expects($this->exactly(4))
            ->method('getQuery')
            ->will($this->returnValue($this->query));

        $this->adapter->upload($files, $mode);
    }

    /**
     * @expectedException \Exception
     */
    public function testUploadError()
    {
        $mode = 'add';
        $files = [
            'some/path/to/file.yml' => 'api/path/test.yml',
        ];

        $this->adapter->setProjectId(1);

        $this->client->expects($this->once())
            ->method('createRequest')
            ->will($this->returnValue($this->request));
        $this->request->expects($this->once())
            ->method('send')
            ->will($this->throwException(new \Exception('test')));
        $this->request->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($this->query));

        $this->adapter->upload($files, $mode);
    }

    /**
     * Test logging exception
     */
    public function testCreateDirError()
    {
        $dirs = ['some/path'];

        $this->client->expects($this->once())
            ->method('createRequest')
            ->will($this->returnValue($this->request));
        $this->request->expects($this->once())
            ->method('send')
            ->will($this->returnValue($this->response));
        $this->request->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($this->query));
        $this->response->expects($this->once())
            ->method('json')
            ->will($this->returnValue(['success' => true]));

        $this->logger->expects($this->atLeastOnce())
            ->method('info');

        $this->adapter->createDirectories($dirs);
    }

    /**
     * Test logging exception while uploading file
     */
    public function testUploadFiles()
    {
        $files = [
            'some/path/to/file.yml' => 'api/path/test.yml',
        ];

        $this->client->expects($this->once())
            ->method('createRequest')
            ->will($this->returnValue($this->request));
        $this->request->expects($this->once())
            ->method('send')
            ->will($this->throwException(new \Exception('test')));
        $this->request->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($this->query));

        $this->logger->expects($this->once())
            ->method('error');

        $this->adapter->uploadFiles($files, 'add');
    }

    public function testDownload()
    {
        $locale = 'en';
        $path =  sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'oro-trans' . DIRECTORY_SEPARATOR . 'target.zip';

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


        $res = $this->adapter->download($path, [$locale]);
        $this->assertTrue($res);
    }

    /**
     * test upload empty files array
     */
    public function testUploadEmpty()
    {
        $this->assertFalse($this->adapter->upload([]));
    }
}
