<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Entity;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;

class TranslationTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Translation */
    protected $translation;

    protected function setUp()
    {
        $this->translation = new Translation();
    }

    public function testGettersAndSetters()
    {
        $this->assertNull($this->translation->getId());
        $this->assertNull($this->translation->getTranslationKey());
        $this->assertNull($this->translation->getValue());
        $this->assertNull($this->translation->getLanguage());
        $this->assertEquals(Translation::SCOPE_SYSTEM, $this->translation->getScope());

        $language = new Language();
        $translationKey = new TranslationKey();

        $this->translation
            ->setValue('Test value')
            ->setLanguage($language)
            ->setTranslationKey($translationKey)
            ->setScope(Translation::SCOPE_UI);

        $this->assertNull($this->translation->getId());
        $this->assertEquals('Test value', $this->translation->getValue());
        $this->assertSame($language, $this->translation->getLanguage());
        $this->assertSame($translationKey, $this->translation->getTranslationKey());
        $this->assertEquals(Translation::SCOPE_UI, $this->translation->getScope());
    }
}
