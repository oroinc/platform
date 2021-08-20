<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\AbstractFixtureWithTags;
use Oro\Bundle\TranslationBundle\Entity\Language;

class LoadLocalizationWithTagsData extends AbstractFixtureWithTags
{
    public const LOCALIZATION_WITH_TAG_1 = 'locale_with_tag_1';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $repository = $manager->getRepository(Language::class);
        $language = $repository->findOneBy([]);

        $localization = new Localization();
        $localization->setName(self::LOCALIZATION_WITH_TAG_1);
        $localization->setDefaultTitle($this->getTextWithTags());
        $localization->setLanguage($language);
        $localization->setFormattingCode('en_US');

        $manager->persist($localization);
        $manager->flush();

        $this->setReference(self::LOCALIZATION_WITH_TAG_1, $localization);
    }
}
