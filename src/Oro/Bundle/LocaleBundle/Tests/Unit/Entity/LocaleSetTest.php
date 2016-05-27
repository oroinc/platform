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
            ['name', 'test_name'],
            ['i18nCode', 'i18n_test_code'],
            ['l10nCode', 'l10n_test_code'],
            ['parentLocaleSet', new LocaleSet()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ]);
        $this->assertPropertyCollections(new LocaleSet(), [
            ['childLocaleSets', new LocaleSet()],
        ]);
    }

    /**
     * @param bool $expected
     * @param \DateTime $date
     *
     * @dataProvider isUpdatedAtSetDataProvider
     */
    public function testIsUpdatedAtSet($expected, \DateTime $date = null)
    {
        $entity = new LocaleSet();
        $entity->setUpdatedAt($date);
        $this->assertEquals($expected, $entity->isUpdatedAtSet());
    }

    /**
     * @return array
     */
    public function isUpdatedAtSetDataProvider()
    {
        return [
            ['expected' => true, 'date' => new \DateTime()],
            ['expected' => false, 'date' => null],
        ];
    }
}
