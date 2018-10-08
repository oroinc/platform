<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Entity;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class TranslationTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new Translation(), [
            ['id', 1],
            ['translationKey', new TranslationKey()],
            ['value', 'test_value'],
            ['language', new Language()],
            ['scope', Translation::SCOPE_SYSTEM],
        ]);
    }
}
