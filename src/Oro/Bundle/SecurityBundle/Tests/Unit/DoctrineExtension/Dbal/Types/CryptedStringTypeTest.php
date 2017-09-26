<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DoctrineExtension\Dbal\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;

use Oro\Bundle\SecurityBundle\DoctrineExtension\Dbal\Types\CryptedStringType;
use Oro\Bundle\SecurityBundle\Encoder\RepetitiveCrypter;

class CryptedStringTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var CryptedStringType */
    protected $fieldType;

    protected function setUp()
    {
        $crypter = $this->getMockBuilder(RepetitiveCrypter::class)
            ->disableOriginalConstructor()
            ->getMock();
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

    public function testGetName()
    {
        $this->assertEquals('crypted_string', $this->fieldType->getName());
    }

    public function testConvertToDatabaseValue()
    {
        $testString = 'test';
        $platform = $this->getMockBuilder(AbstractPlatform::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals(
            'crypted_' . $testString,
            $this->fieldType->convertToDatabaseValue($testString, $platform)
        );
    }

    public function testConvertToPHPValue()
    {
        $testString = 'test';
        $platform = $this->getMockBuilder(AbstractPlatform::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals(
            $testString,
            $this->fieldType->convertToPHPValue('crypted_' . $testString, $platform)
        );
    }
}
