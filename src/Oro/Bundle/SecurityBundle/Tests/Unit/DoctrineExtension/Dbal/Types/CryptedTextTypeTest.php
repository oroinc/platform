<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DoctrineExtension\Dbal\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\SecurityBundle\DoctrineExtension\Dbal\Types\CryptedTextType;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use PHPUnit\Framework\TestCase;

final class CryptedTextTypeTest extends TestCase
{
    private CryptedTextType $type;

    protected function setUp(): void
    {
        $crypter = $this->createMock(SymmetricCrypterInterface::class);
        $crypter->expects(self::any())
            ->method('encryptData')
            ->willReturnCallback(function ($value) {
                return 'crypted_' . $value;
            });
        $crypter->expects(self::any())
            ->method('decryptData')
            ->willReturnCallback(function ($value) {
                return str_replace('crypted_', '', $value);
            });

        CryptedTextType::setCrypter($crypter);

        $this->type = new CryptedTextType();
    }

    public function testGetName(): void
    {
        self::assertEquals(CryptedTextType::TYPE, $this->type->getName());
    }

    public function testRequiresSQLCommentHint(): void
    {
        self::assertTrue($this->type->requiresSQLCommentHint($this->createMock(AbstractPlatform::class)));
    }

    public function testConvertToDatabaseValue(): void
    {
        $testString = 'test';
        self::assertEquals(
            'crypted_' . $testString,
            $this->type->convertToDatabaseValue($testString, $this->createMock(AbstractPlatform::class))
        );
    }

    public function testConvertToPHPValue(): void
    {
        $testString = 'test';
        self::assertEquals(
            $testString,
            $this->type->convertToPHPValue('crypted_' . $testString, $this->createMock(AbstractPlatform::class))
        );
    }
}
