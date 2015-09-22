<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Encoder;

use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

class McryptTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test two way encoding/decoding
     *
     * @dataProvider keyDataProvider
     * @param string $key
     */
    public function testEncodeDecode($key)
    {
        $someData = 'someValue';

        $encryptor = new Mcrypt($key);

        $encrypted = $encryptor->encryptData($someData);
        $this->assertInternalType('string', $encrypted);
        $this->assertNotEquals($someData, $encrypted);

        $this->assertEquals($someData, $encryptor->decryptData($encrypted));
    }

    /**
     * @dataProvider keyDataProvider
     * @param string $key
     */
    public function testEncodeDecodeDifferentInstances($key)
    {
        $someData = 'someValue';

        $encryptor = new Mcrypt($key);
        $encrypted = $encryptor->encryptData($someData);
        $this->assertNotEquals($someData, $encrypted);

        $newInstance = new Mcrypt($key);
        $this->assertEquals($someData, $newInstance->decryptData($encrypted));
    }

    public function keyDataProvider()
    {
        return array(
            '0 length' => array(''),
            '1 length' => array('a'),
            'test key' => array('someKey'),
            '32 length' => array('1234567890123456789012'),
            '33 length' => array('12345678901234567890123')
        );
    }
}
