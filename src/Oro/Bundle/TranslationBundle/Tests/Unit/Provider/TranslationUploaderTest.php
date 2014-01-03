<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Provider\TranslationUploader;

class TranslationUploaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $adapter;

    /** @var TranslationUploader */
    protected $uploader;

    public function setUp()
    {
        $this->markTestSkipped('Fix');
        $this->adapter = $this->getMock(
            'Oro\Bundle\TranslationBundle\Provider\CrowdinAdapter',
            array(),
            array('some-api-key', 'http://service-url.tld/api/')
        );

        $this->uploader = new TranslationUploader($this->adapter);
    }

    public function tearDown()
    {
        unset($this->adapter);
        unset($this->uploader);
    }

    /**
     * Test upload method
     */
    public function testUpload()
    {
        $mode = 'add';
        $callback = function ($logItem) {

        };

        $this->adapter->expects($this->once())
            ->method('setProgressCallback')
            ->with($callback);

        $this->adapter->expects($this->once())
            ->method('upload')
            ->with($this->isType('array'), $mode);

        $this->uploader->setAdapter($this->adapter);
        $this->uploader->upload(__DIR__ . '/../Fixtures/Resources/lang-pack/', $mode, $callback);
    }
}
