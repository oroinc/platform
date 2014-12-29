<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Encoder;

use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

class McryptTest extends \PHPUnit_Framework_TestCase
{
    const TEST_KEY = 'someKey';

    /** @var Mcrypt */
    protected $encryptor;

    protected function setUp()
    {
        $this->encryptor = $this->getInstance();
    }

    protected function tearDown()
    {
        unset($this->encryptor);
    }

    /**
     * Test two way encoding/decoding
     */
    public function testEncodeDecode()
    {
        $someData = 'someValue';

        $encrypted = $this->encryptor->encryptData($someData);
        $this->assertInternalType('string', $encrypted);

        $this->assertEquals($someData, $this->encryptor->decryptData($encrypted));
    }

    public function testEncodeDecodeDifferentInstances()
    {
        $someData = 'someValue';

        $encrypted = $this->encryptor->encryptData($someData);

        $newInstance = $this->getInstance();
        $this->assertEquals($someData, $newInstance->decryptData($encrypted));
    }

    /**
     * @dataProvider keyDataProvider
     * @param string $key
     */
    public function testKeyLengthChecks($key)
    {
        new Mcrypt($key);
    }

    public function keyDataProvider()
    {
        return array(
            'null key' => array(null),
            '0 length' => array(''),
            '1 length' => array('a'),
            '32 length' => array('1234567890123456789012'),
            '33 length' => array('12345678901234567890123')
        );
    }

    /**
     * @return Mcrypt
     */
    protected function getInstance()
    {
        return new Mcrypt(self::TEST_KEY);
    }
}
