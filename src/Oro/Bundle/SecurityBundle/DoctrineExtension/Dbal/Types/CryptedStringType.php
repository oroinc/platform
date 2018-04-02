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

    /**
     * @param SymmetricCrypterInterface $crypter
     */
    public static function setCrypter(SymmetricCrypterInterface $crypter)
    {
        static::$crypter = $crypter;
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return self::TYPE;
    }

    /** {@inheritdoc} */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return static::$crypter->encryptData($value);
    }

    /** {@inheritdoc} */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return static::$crypter->decryptData($value);
    }
    
    /** {@inheritdoc} */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
