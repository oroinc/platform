<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Provider\CrowdinAdapter;

class CrowdinAdapterTest extends \PHPUnit_Framework_TestCase
{
    /** @var CrowdinAdapter */
    protected $adapter;

    public function setUp()
    {
        $phpunit = $this;
        $this->callback = function ($item) use ($phpunit) {
            $phpunit->assertInternalType('string', $item);
        };

        $this->adapter = $this->getMock(
            'Oro\Bundle\TranslationBundle\Provider\CrowdinAdapter',
            array('request', 'createDirectories', 'uploadFiles'),
            array('some-api-key', 'http://service-url.tld/api/')
        );

        $this->adapter->setProgressCallback($this->callback);
    }

    public function tearDown()
    {
        unset($this->adapter);
    }

    /**
     * Test upload method
     */
    public function testUpload()
    {
        $mode = 'add';
        $files = array(
            'some/path/to/file.yml' => 'api/path/test.yml',
        );

        $dirs = array('some', 'some/path', 'some/path/to');
        $dirs = array_combine($dirs, $dirs);

        $this->adapter->expects($this->once())
            ->method('createDirectories')
            ->with($dirs)
            ->will($this->returnSelf());

        $this->adapter->expects($this->once())
            ->method('uploadFiles')
            ->with($files, $mode);

        $this->adapter->setProjectId(1);
        $this->adapter->upload($files, $mode);
    }

    /**
     * test upload empty files array
     */
    public function testUploadEmpty()
    {
        $this->assertFalse($this->adapter->upload(array()));
    }

    /**
     * Test good scenario uploadFiles
     */
    public function testUploadFiles()
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
