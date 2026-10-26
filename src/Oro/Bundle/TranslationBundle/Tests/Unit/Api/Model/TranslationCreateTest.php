<?php

declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Api\Model;

use Oro\Bundle\TranslationBundle\Api\Model\TranslationCreate;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class TranslationCreateTest extends TestCase
{
    public function testSynchronizeNewTranslationKeyId(): void
    {
        $translation = new TranslationCreate('messages', 'option.name', 0, 'fr_FR');
        $key = new TranslationKey();

        self::assertEquals('', $translation->getId());
        self::assertNull($translation->getNewTranslationKey());

        $translation->synchronizeNewTranslationKeyId();
        self::assertEquals('', $translation->getId());

        $translation->setNewTranslationKey($key);
        self::assertEquals($key, $translation->getNewTranslationKey());
        $translation->synchronizeNewTranslationKeyId();
        self::assertEquals('', $translation->getId());

        ReflectionUtil::setId($key, 42);
        $translation->synchronizeNewTranslationKeyId();
        self::assertEquals('42-fr_FR', $translation->getId());
    }
}
