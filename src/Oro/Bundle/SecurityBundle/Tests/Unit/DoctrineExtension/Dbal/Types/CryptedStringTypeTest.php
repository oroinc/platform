<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DoctrineExtension\Dbal\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\SecurityBundle\DoctrineExtension\Dbal\Types\CryptedStringType;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class CryptedStringTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var CryptedStringType */
    protected $fieldType;

    protected function setUp()
    {
        $crypter = $this->createMock(SymmetricCrypterInterface::class);
        $crypter->expects($this->any())
            ->method('encryptData')
            ->willReturnCallback(
                function ($value) {
                    return 'crypted_' . $value;
                }
            );
        $crypter->expects($this->any())
            ->method('decryptData')
            ->willReturnCallback(
                function ($value) {
                    return str_replace('crypted_', '', $value);
                }
            );
        CryptedStringType::setCrypter($crypter);
        if (!CryptedStringType::hasType('crypted_string')) {
            CryptedStringType::addType('crypted_string', CryptedStringType::class);
        }
        $this->fieldType = CryptedStringType::getType('crypted_string');
    }

    public function testConvertToDatabaseValue()
    {
        $testString = 'test';
        $this->assertEquals(
            'crypted_' . $testString,
            $this->fieldType->convertToDatabaseValue($testString, $this->createMock(AbstractPlatform::class))
        );
    }

    public function testConvertToPHPValue()
    {
        $testString = 'test';
        $this->assertEquals(
            $testString,
            $this->fieldType->convertToPHPValue('crypted_' . $testString, $this->createMock(AbstractPlatform::class))
        );
    }
}
