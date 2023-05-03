<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class MenuUpdateTraitTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 42],
            ['key', 'page_wrapper'],
            ['parentKey', 'page_container'],
            ['uri', 'uri'],
            ['menu', 'main_menu'],
            ['scope', new Scope()],
            ['active', true],
            ['divider', true],
            ['custom', true],
            ['synthetic', true],
            ['priority', 1],
        ];

        $update = new MenuUpdateStub();

        self::assertPropertyAccessors($update, $properties);
    }

    public function testTitleAccessors(): void
    {
        $update = new MenuUpdateStub();
        self::assertEmpty($update->getTitles()->toArray());

        $firstTitle = $this->createLocalizedValue();

        $secondTitle = $this->createLocalizedValue();

        $update->addTitle($firstTitle)
            ->addTitle($secondTitle)
            ->addTitle($secondTitle);

        self::assertCount(2, $update->getTitles()->toArray());

        self::assertEquals([$firstTitle, $secondTitle], array_values($update->getTitles()->toArray()));

        $update->removeTitle($firstTitle)
            ->removeTitle($firstTitle);

        self::assertEquals([$secondTitle], array_values($update->getTitles()->toArray()));
    }

    public function testDescriptionAccessors(): void
    {
        $update = new MenuUpdateStub();
        self::assertEmpty($update->getDescriptions()->toArray());

        $firstDescription = $this->createLocalizedValue();

        $secondDescription = $this->createLocalizedValue();

        $update->addDescription($firstDescription)
            ->addDescription($secondDescription)
            ->addDescription($secondDescription);

        self::assertCount(2, $update->getDescriptions()->toArray());

        self::assertEquals(
            [$firstDescription, $secondDescription],
            array_values($update->getDescriptions()->toArray())
        );

        $update->removeDescription($firstDescription)
            ->removeDescription($firstDescription);

        self::assertEquals([$secondDescription], array_values($update->getDescriptions()->toArray()));

        $defaultDescriptionString = 'default description string';
        $update->setDefaultDescription($defaultDescriptionString);
        self::assertEquals($defaultDescriptionString, $update->getDefaultDescription()->getText());
    }

    /**
     * @param boolean $default
     *
     * @return LocalizedFallbackValue
     */
    protected function createLocalizedValue($default = false): LocalizedFallbackValue
    {
        $localized = (new LocalizedFallbackValue())->setString('some string');

        if (!$default) {
            $localized->setLocalization(new Localization());
        }

        return $localized;
    }
}
