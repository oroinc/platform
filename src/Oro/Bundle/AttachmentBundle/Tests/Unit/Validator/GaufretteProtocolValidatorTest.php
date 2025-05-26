<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator;

use Oro\Bundle\AttachmentBundle\Validator\GaufretteProtocolValidator;
use Oro\Bundle\AttachmentBundle\Validator\ProtocolValidatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GaufretteProtocolValidatorTest extends TestCase
{
    private ProtocolValidatorInterface&MockObject $innerValidator;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerValidator = $this->createMock(ProtocolValidatorInterface::class);
    }

    public function testIsSupportedProtocolForGaufretteProtocol(): void
    {
        $protocol = 'gaufrette';
        $readonlyProtocol = 'gaufrette-readonly';

        $this->innerValidator->expects(self::exactly(2))
            ->method('isSupportedProtocol')
            ->withConsecutive(
                [$protocol],
                [$readonlyProtocol]
            )
            ->willReturn(false);

        $validator = new GaufretteProtocolValidator($this->innerValidator, [$protocol, $readonlyProtocol]);
        self::assertTrue($validator->isSupportedProtocol($protocol));
        self::assertTrue($validator->isSupportedProtocol($readonlyProtocol));
    }

    public function testIsSupportedProtocolForGaufretteProtocolWhenItIsNotConfigured(): void
    {
        $protocol = 'gaufrette';

        $this->innerValidator->expects(self::once())
            ->method('isSupportedProtocol')
            ->with($protocol)
            ->willReturn(false);

        $validator = new GaufretteProtocolValidator($this->innerValidator, ['', '']);
        self::assertFalse($validator->isSupportedProtocol($protocol));
    }

    public function testIsSupportedProtocolForProtocolSupportedByInnerValidator(): void
    {
        $protocol = 'http';

        $this->innerValidator->expects(self::once())
            ->method('isSupportedProtocol')
            ->with($protocol)
            ->willReturn(true);

        $validator = new GaufretteProtocolValidator($this->innerValidator, ['gaufrette', 'gaufrette-readonly']);
        self::assertTrue($validator->isSupportedProtocol($protocol));
    }

    public function testIsSupportedProtocolForProtocolNotSupportedByInnerValidator(): void
    {
        $protocol = 'http';

        $this->innerValidator->expects(self::once())
            ->method('isSupportedProtocol')
            ->with($protocol)
            ->willReturn(false);

        $validator = new GaufretteProtocolValidator($this->innerValidator, ['gaufrette', 'gaufrette-readonly']);
        self::assertFalse($validator->isSupportedProtocol($protocol));
    }
}
