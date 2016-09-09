<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Entity;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;

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
        $this->assertNull($this->translation->getKey());
        $this->assertNull($this->translation->getValue());
        $this->assertNull($this->translation->getLanguage());
        $this->assertNull($this->translation->getDomain());
        $this->assertEquals(Translation::SCOPE_SYSTEM, $this->translation->getScope());

        $language = new Language();

        $this->translation
            ->setKey('test.key')
            ->setValue('Test value')
            ->setLanguage($language)
            ->setDomain('messages')
            ->setScope(Translation::SCOPE_UI);

        $this->assertNull($this->translation->getId());
        $this->assertEquals('test.key', $this->translation->getKey());
        $this->assertEquals('Test value', $this->translation->getValue());
        $this->assertSame($language, $this->translation->getLanguage());
        $this->assertEquals('messages', $this->translation->getDomain());
        $this->assertEquals(Translation::SCOPE_UI, $this->translation->getScope());
    }
}
