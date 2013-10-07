<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Provider\CrowdinAdapter;

class CrowdinAdapterTest extends \PHPUnit_Framework_TestCase
{
    /** @var CrowdinAdapter */
    protected $adapter;

    public function setUp()
    {
        $this->callback = function () {

        };

        $this->adapter = $this->getMock(
            'Oro\Bundle\TranslationBundle\Provider\CrowdinAdapter',
            array('request'),
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
            '/some/path/to/file' => '/api/path',
        );

        $this->adapter->upload($files, $mode);
    }
}
