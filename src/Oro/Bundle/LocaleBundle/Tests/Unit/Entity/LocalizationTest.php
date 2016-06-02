<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

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
            ['parentLocalization', new Localization()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ]);
        $this->assertPropertyCollections(new Localization(), [
            ['childLocalizations', new Localization()],
            ['titles', new LocalizedFallbackValue()],
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

    public function testConstruct()
    {
        $entity = new Localization();
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $entity->getChildLocalizations());
        $this->assertEmpty($entity->getChildLocalizations()->toArray());
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $entity->getTitles());
        $this->assertEmpty($entity->getTitles()->toArray());
    }

    public function testTitleAccessor()
    {
        $entity = new Localization();
        $this->assertEmpty($entity->getTitles()->toArray());

        $firstTitle = $this->createLocalizedValue();

        $secondTitle = $this->createLocalizedValue();

        $entity->addTitle($firstTitle)
            ->addTitle($secondTitle)
            ->addTitle($secondTitle);

        $this->assertEquals(
            2,
            count($entity->getTitles()->toArray())
        );

        $this->assertEquals([$firstTitle, $secondTitle], array_values($entity->getTitles()->toArray()));

        $entity->removeTitle($firstTitle)
            ->removeTitle($firstTitle);

        $this->assertEquals([$secondTitle], array_values($entity->getTitles()->toArray()));
    }

    public function testGetDefaultTitle()
    {
        $defaultTitle = $this->createLocalizedValue(true);
        $localizedTitle = $this->createLocalizedValue();

        $entity = new Localization();
        $entity->addTitle($defaultTitle)
            ->addTitle($localizedTitle);

        $this->assertEquals($defaultTitle, $entity->getDefaultTitle());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage There must be only one default title
     */
    public function testGetDefaultTitleException()
    {
        $titles = [$this->createLocalizedValue(true), $this->createLocalizedValue(true)];
        $entity = new Localization();

        foreach ($titles as $title) {
            $entity->addTitle($title);
        }

        $entity->getDefaultTitle();
    }

    public function testSetDefaultTitle()
    {
        $entity = new Localization();
        $entity->setDefaultTitle('test_title_string1');
        $this->assertEquals('test_title_string1', $entity->getDefaultTitle());

        // check second time to make sure we don't have an exception in getter
        $entity->setDefaultTitle('test_title_string2');
        $this->assertEquals('test_title_string2', $entity->getDefaultTitle());
    }
    
    /**
     * @param bool|false $default
     *
     * @return LocalizedFallbackValue
     */
    protected function createLocalizedValue($default = false)
    {
        $localized = (new LocalizedFallbackValue())->setString('some string');

        if (!$default) {
            $localized->setLocale(new Locale());
        }

        return $localized;
    }
}
