<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityConfigBundle\Provider\AttributeFamilyEntityNameProvider;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\AttributeFamilyStub as AttributeFamily;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class AttributeFamilyEntityNameProviderTest extends TestCase
{
    private AttributeFamilyEntityNameProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new AttributeFamilyEntityNameProvider();
    }

    private function getAttributeFamilyLabel(string $string, ?Localization $localization = null): LocalizedFallbackValue
    {
        $value = new LocalizedFallbackValue();
        $value->setString($string);
        $value->setLocalization($localization);

        return $value;
    }

    private function getLocalization(string $code): Localization
    {
        $language = new Language();
        $language->setCode($code);

        $localization = new Localization();
        ReflectionUtil::setId($localization, 123);
        $localization->setLanguage($language);

        return $localization;
    }

    public function testGetNameForUnsupportedEntity(): void
    {
        $this->assertFalse(
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', new \stdClass())
        );
    }

    public function testGetNameForShortFormat(): void
    {
        $this->assertFalse(
            $this->provider->getName(EntityNameProviderInterface::SHORT, 'en', new AttributeFamily())
        );
    }

    public function testGetName(): void
    {
        $family = new AttributeFamily();
        $family->setCode('CODE');
        $family->addLabel($this->getAttributeFamilyLabel('default label'));
        $family->addLabel($this->getAttributeFamilyLabel('localized label', $this->getLocalization('en')));

        $this->assertEquals(
            'default label',
            $this->provider->getName(EntityNameProviderInterface::FULL, null, $family)
        );
    }

    public function testGetNameForLocalization(): void
    {
        $family = new AttributeFamily();
        $family->setCode('CODE');
        $family->addLabel($this->getAttributeFamilyLabel('default label'));
        $family->addLabel($this->getAttributeFamilyLabel('localized label', $this->getLocalization('en')));

        $this->assertEquals(
            'localized label',
            $this->provider->getName(EntityNameProviderInterface::FULL, $this->getLocalization('en'), $family)
        );
    }

    public function testGetNameForEmptyName(): void
    {
        $family = new AttributeFamily();
        $family->setCode('CODE');
        $family->addLabel($this->getAttributeFamilyLabel(''));

        $this->assertEquals(
            'CODE',
            $this->provider->getName(EntityNameProviderInterface::FULL, null, $family)
        );
    }

    public function testGetNameDQLForUnsupportedEntity(): void
    {
        self::assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, 'en', \stdClass::class, 'entity')
        );
    }

    public function testGetNameDQLForShortFormat(): void
    {
        $this->assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::SHORT, 'en', AttributeFamily::class, 'family')
        );
    }

    public function testGetNameDQL(): void
    {
        self::assertEquals(
            'CAST((SELECT COALESCE(NULLIF(COALESCE(family_l.string, family_l.text), \'\'), family.code)'
            . ' FROM Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue family_l'
            . ' WHERE family_l MEMBER OF family.labels AND family_l.localization IS NULL) AS string)',
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, null, AttributeFamily::class, 'family')
        );
    }

    public function testGetNameDQLForLocalization(): void
    {
        self::assertEquals(
            'CAST((SELECT COALESCE(family_l.string, family_l.text,'
            . ' NULLIF(COALESCE(family_dl.string, family_dl.text), \'\'), family.code)'
            . ' FROM Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue family_dl'
            . ' LEFT JOIN Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue family_l'
            . ' WITH family_l MEMBER OF family.labels AND family_l.localization = 123'
            . ' WHERE family_dl MEMBER OF family.labels AND family_dl.localization IS NULL) AS string)',
            $this->provider->getNameDQL(
                EntityNameProviderInterface::FULL,
                $this->getLocalization('en'),
                AttributeFamily::class,
                'family'
            )
        );
    }
}
