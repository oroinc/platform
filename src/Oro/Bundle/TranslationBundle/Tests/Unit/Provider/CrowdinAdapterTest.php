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
    protected $request;

    public function setUp()
    {
        $this->logger = $this->getMock('Psr\Log\LoggerInterface');
        $this->request = $this->getMock('Oro\Bundle\TranslationBundle\Provider\ApiRequestInterface');

        $this->adapter = new CrowdinAdapter('http://service-url.tld/api/', $this->request);
        $this->adapter->setApiKey('some-api-key');
        $this->adapter->setLogger($this->logger);
    }

    public function tearDown()
    {
        unset($this->logger, $this->request, $this->adapter);
    }

    /**
     * Test upload method
     */
    public function testUpload()
    {
        $mode = 'add';
        $files = [
            'some/path/to/file.yml' => 'api/path/test.yml',
        ];

        $dirs = array('some', 'some/path', 'some/path/to');
        $dirs = array_combine($dirs, $dirs);

        $this->request->expects($this->once())
            ->method('setOptions');

        $this->request->expects($this->once())
            ->method('execute');

        $this->adapter->setProjectId(1);
        $this->adapter->upload($files, $mode);
    }

    /**
     * test upload empty files array
     */
    public function testUploadEmpty()
    {
        $this->assertFalse($this->adapter->upload([]));
    }

    /**
     * Test good scenario uploadFiles
     */
    public function testUploadFiles()
    {
        $this->markTestSkipped();
        $adapter = $this->getMock(
            'Oro\Bundle\TranslationBundle\Provider\CrowdinAdapter',
            array('request', 'addFile', 'notifyProgress'),
            array('some-api-key', 'http://service-url.tld/api/')
        );

        $mode = 'add';
        $files = array(
            '/some/path/to/file.yml' => '/api/path/test.yml',
        );

        $adapter->expects($this->once())
            ->method('addFile')
            ->will($this->returnValue(true));

        $adapter->expects($this->once())
            ->method('notifyProgress')
            ->will($this->returnValue(true));

        $result = $adapter->uploadFiles($files, $mode);

        $this->assertCount(1, $result['results']);
    }

    /**
     * Test bad scenario for uploadFiles
     *
     * @throws \Exception
     */
    public function testExceptionUploadFiles()
    {
        $adapter = $this->getMock(
            'Oro\Bundle\TranslationBundle\Provider\CrowdinAdapter',
            array('request', 'addFile', 'notifyProgress'),
            array('some-api-key', 'http://service-url.tld/api/')
        );

        $mode = 'add';
        $files = array(
            '/some/path/to/file.yml' => '/api/path/test.yml',
        );

        $adapter->expects($this->once())
            ->method('addFile')
            ->will($this->throwException(new \Exception('some message')));

        $adapter->expects($this->once())
            ->method('notifyProgress')
            ->will($this->returnValue(true));

        $result = $adapter->uploadFiles($files, $mode);

        $this->assertCount(1, $result['failed']);
    }
}
