<?php

namespace Oro\Bundle\TranslationBundle\Tests\Entity;

use Oro\Bundle\TranslationBundle\Entity\Translation;

class TranslationTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Translation */
    protected $translation;

    public function setUp()
    {
        $this->translation = new Translation();
    }

    public function testGettersAndSetters()
    {
        $this->assertNull($this->translation->getId());
        $this->assertNull($this->translation->getKey());
        $this->assertNull($this->translation->getValue());
        $this->assertNull($this->translation->getLocale());
        $this->assertNull($this->translation->getDomain());

        $this->translation
            ->setKey('test.key')
            ->setValue('Test value')
            ->setLocale('en')
            ->setDomain('messages');

        $this->assertNull($this->translation->getId());
        $this->assertEquals('test.key', $this->translation->getKey());
        $this->assertEquals('Test value', $this->translation->getValue());
        $this->assertEquals('en', $this->translation->getLocale());
        $this->assertEquals('messages', $this->translation->getDomain());
    }
}
