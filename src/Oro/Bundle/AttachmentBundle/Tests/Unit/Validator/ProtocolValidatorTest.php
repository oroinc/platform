<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator;

use Oro\Bundle\AttachmentBundle\Validator\ProtocolValidator;

class ProtocolValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider safeProtocolDataProvider
     */
    public function testIsSafeProtocol($isSafe, $path)
    {
        $validator = new ProtocolValidator();
        self::assertEquals($isSafe, $validator->isSupportedProtocol($path));
    }

    public function safeProtocolDataProvider()
    {
        return [
            [true, 'file'],
            [true, 'http'],
            [true, 'https'],
            [true, 'ftp'],
            [true, 'ftps'],
            [true, 'ssh2.sftp'],
            [false, 'phar'],
            [false, 'data'],
            [false, 'another']
        ];
    }
}
