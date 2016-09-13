<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Entity;

use Oro\Bundle\TranslationBundle\Entity\TranslationKey;

class TranslationTest extends \PHPUnit_Framework_TestCase
{
    /** @var  TranslationKey */
    protected $translationKey;

    protected function setUp()
    {
        $this->translationKey = new TranslationKey();
    }

    public function testGettersAndSetters()
    {
        $this->assertNull($this->translationKey->getId());
        $this->assertNull($this->translationKey->getKey());
        $this->assertNull($this->translationKey->getDomain());

        $this->translationKey
            ->setDomain('Test Domain')
            ->setKey('Test Key');

        $this->assertNull($this->translationKey->getId());
        $this->assertEquals('Test Domain', $this->translationKey->getDomain());
        $this->assertEquals('Test Key', $this->translationKey->getKey());
    }
}
