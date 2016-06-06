<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\LocaleBundle\Entity\Localization;

class LoadLocalizationData extends AbstractFixture
{
    /**
     * @var array
     */
    protected $localizations = [
        [
            'language' => 'en_US',
            'formatting' => 'en_US',
            'parent' => null,
            'title' => 'English (United States)',
        ],
        [
            'language' => 'en_CA',
            'formatting' => 'en_CA',
            'parent' => 'en_US',
            'title' => 'English (Canada)',
        ],
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $registry = [];
        foreach ($this->localizations as $item) {
            $code = $item['language'];
            $localization = new Localization();
            $localization
                ->setLanguageCode($item['language'])
                ->setFormattingCode($item['formatting'])
                ->setName($item['title']);

            if ($item['parent']) {
                $localization->setParentLocalization($registry[$item['parent']]);
            }
            $registry[$code] = $localization;

            $manager->persist($localization);

            $this->addReference($code, $localization);
        }

        $manager->flush();
        $manager->clear();
    }
}
