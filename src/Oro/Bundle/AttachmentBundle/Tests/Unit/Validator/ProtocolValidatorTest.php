<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator;

use Oro\Bundle\AttachmentBundle\Validator\ProtocolValidator;
use PHPUnit\Framework\TestCase;

class ProtocolValidatorTest extends TestCase
{
    /**
     * @dataProvider protocolDataProvider
     */
    public function testIsSupportedProtocol($isSupported, $protocol): void
    {
        $validator = new ProtocolValidator();
        self::assertEquals($isSupported, $validator->isSupportedProtocol($protocol));
    }

    public function protocolDataProvider(): array
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
