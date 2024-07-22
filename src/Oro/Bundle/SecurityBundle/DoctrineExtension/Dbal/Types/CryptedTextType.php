<?php

namespace Oro\Bundle\SecurityBundle\DoctrineExtension\Dbal\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * Doctrine type that stores text data in crypted format.
 */
class CryptedTextType extends TextType
{
    public const string TYPE = 'crypted_text';

    private static SymmetricCrypterInterface $crypter;

    public static function setCrypter(SymmetricCrypterInterface $crypter): void
    {
        static::$crypter = $crypter;
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return self::TYPE;
    }

    /** {@inheritdoc} */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        return static::$crypter->encryptData($value);
    }

    /** {@inheritdoc} */
    public function convertToPHPValue($value, AbstractPlatform $platform): string
    {
        return static::$crypter->decryptData($value);
    }

    /** {@inheritdoc} */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
