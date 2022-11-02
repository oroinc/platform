<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Encoder;

use Oro\Bundle\SecurityBundle\Encoder\DefaultCrypter;

class DefaultCrypterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test two way encoding/decoding
     *
     * @dataProvider keyDataProvider
     */
    public function testEncodeDecode(string $key)
    {
        $someData = 'someValue';

        $encryptor = new DefaultCrypter($key);

        $encrypted = $encryptor->encryptData($someData);
        $this->assertIsString($encrypted);
        $this->assertNotEquals($someData, $encrypted);

        $this->assertEquals($someData, $encryptor->decryptData($encrypted));
    }

    /**
     * @dataProvider keyDataProvider
     */
    public function testEncodeDecodeDifferentInstances(string $key)
    {
        $someData = 'someValue';

        $encryptor = new DefaultCrypter($key);
        $encrypted = $encryptor->encryptData($someData);
        $this->assertNotEquals($someData, $encrypted);

        $newInstance = new DefaultCrypter($key);
        $this->assertEquals($someData, $newInstance->decryptData($encrypted));
        $this->assertNotEquals($encrypted, $newInstance->encryptData($someData));
    }

    public function keyDataProvider(): array
    {
        return [
            '0 length' => [''],
            '1 length' => ['a'],
            'test key' => ['someKey'],
            '32 length' => ['1234567890123456789012'],
            '33 length' => ['12345678901234567890123']
        ];
    }
}
