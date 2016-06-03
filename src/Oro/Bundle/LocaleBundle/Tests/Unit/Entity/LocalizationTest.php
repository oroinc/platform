<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\Localization;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class LocalizationTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new Localization(), [
            ['id', 1],
            ['name', 'test_name'],
            ['languageCode', 'language_test_code'],
            ['formattingCode', 'formatting_test_code'],
            ['parent', new Localization()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ]);
        $this->assertPropertyCollections(new Localization(), [
            ['childs', new Localization()],
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
        $entity = new Localization();
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

    public function testToString()
    {
        $entity = new Localization();
        $expectedString = 'Expected String';
        $entity->setName($expectedString);
        $this->assertEquals($expectedString, $entity->__toString());
    }
}
