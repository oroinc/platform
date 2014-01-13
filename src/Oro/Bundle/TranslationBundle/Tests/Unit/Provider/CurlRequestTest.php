<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Provider\CurlRequest;

class CurlRequestTest extends \PHPUnit_Framework_TestCase
{
    /** @var CurlRequest */
    protected $request;

    public function setUp()
    {
        $this->request = new CurlRequest();
    }

    public function tearDown()
    {
        unset($this->request);
    }

    public function testSetOptions()
    {
        $this->assertTrue($this->request->setOptions([CURLOPT_MAXREDIRS => 1]));
        $this->assertFalse($this->request->setOptions([]));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testExecuteFail()
    {
        $this->request->execute();
    }

    public function testExecute()
    {
        $this->request->setOptions(
            [
                CURLOPT_URL => 'file:///tmp',
            ]
        );

        $this->assertTrue($this->request->execute());
    }
}
