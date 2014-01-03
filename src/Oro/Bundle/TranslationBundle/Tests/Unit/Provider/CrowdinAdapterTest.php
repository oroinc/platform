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

        $this->adapter->setProjectId(1);

        $this->request->expects($this->exactly(4))
            ->method('setOptions');

        $this->request->expects($this->exactly(4))
            ->method('execute')
            ->will(
                $this->returnValue(
                    '<?xml version="1.0" encoding="UTF-8"?><test>test</test>'
                )
            );

        $this->adapter->upload($files, $mode);
    }

    /**
     * Test upload method
     */
    public function testDownload()
    {
        $locale = 'en';
        $path =  './app/cache/dev/target.zip';

        $this->request->expects($this->once())
            ->method('setOptions');

        $this->request->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(true));

        $res = $this->adapter->download($path, $locale);
        $this->assertTrue($res);
        unlink($path);
    }

    /**
     * test upload empty files array
     */
    public function testUploadEmpty()
    {
        $this->assertFalse($this->adapter->upload([]));
    }
}
