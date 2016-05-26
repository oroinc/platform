<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\LocaleSet;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class LocaleSetTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new LocaleSet(), [
            ['id', 1],
            ['name','test_name'],
            ['i18nCode', 'i18n_test_code'],
            ['l10nCode', 'l10n_test_code'],
            ['parentLocaleSet', new LocaleSet()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['updatedAtSet', 1]
        ]);
        $this->assertPropertyCollections(new LocaleSet(), [
            ['childLocaleSets', new LocaleSet()],
        ]);
    }
}
