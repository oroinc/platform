<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator;

use Oro\Bundle\AttachmentBundle\Validator\GaufretteProtocolValidator;
use Oro\Bundle\AttachmentBundle\Validator\ProtocolValidatorInterface;

class GaufretteProtocolValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProtocolValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerValidator;

    protected function setUp(): void
    {
        $this->innerValidator = $this->createMock(ProtocolValidatorInterface::class);
    }

    public function testIsSupportedProtocolForGaufretteProtocol()
    {
        $protocol = 'gaufrette';

        $this->innerValidator->expects(self::once())
            ->method('isSupportedProtocol')
            ->with($protocol)
            ->willReturn(false);

        $validator = new GaufretteProtocolValidator($this->innerValidator, 'gaufrette');
        self::assertTrue($validator->isSupportedProtocol($protocol));
    }

    public function testIsSupportedProtocolForGaufretteProtocolWhenItIsNotConfigured()
    {
        $protocol = 'gaufrette';

        $this->innerValidator->expects(self::once())
            ->method('isSupportedProtocol')
            ->with($protocol)
            ->willReturn(false);

        $validator = new GaufretteProtocolValidator($this->innerValidator, '');
        self::assertFalse($validator->isSupportedProtocol($protocol));
    }

    public function testIsSupportedProtocolForProtocolSupportedByInnerValidator()
    {
        $protocol = 'http';

        $this->innerValidator->expects(self::once())
            ->method('isSupportedProtocol')
            ->with($protocol)
            ->willReturn(true);

        $validator = new GaufretteProtocolValidator($this->innerValidator, 'gaufrette');
        self::assertTrue($validator->isSupportedProtocol($protocol));
    }

    public function testIsSupportedProtocolForProtocolNotSupportedByInnerValidator()
    {
        $protocol = 'http';

        $this->innerValidator->expects(self::once())
            ->method('isSupportedProtocol')
            ->with($protocol)
            ->willReturn(false);

        $validator = new GaufretteProtocolValidator($this->innerValidator, 'gaufrette');
        self::assertFalse($validator->isSupportedProtocol($protocol));
    }
}
