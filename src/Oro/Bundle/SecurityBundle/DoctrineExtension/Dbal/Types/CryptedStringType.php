<?php

namespace Oro\Bundle\SecurityBundle\DoctrineExtension\Dbal\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * Doctrine type that stores string data in crypted format.
 */
class CryptedStringType extends StringType
{
    const TYPE = 'crypted_string';

    /** @var SymmetricCrypterInterface */
    private static $crypter;

    public static function setCrypter(SymmetricCrypterInterface $crypter)
    {
        static::$crypter = $crypter;
    }

    #[\Override]
    public function getName()
    {
        return self::TYPE;
    }

    #[\Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return static::$crypter->encryptData($value);
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return static::$crypter->decryptData($value);
    }

    #[\Override]
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
