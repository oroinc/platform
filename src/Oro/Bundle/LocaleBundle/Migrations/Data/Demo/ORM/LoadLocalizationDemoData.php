<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Intl\Intl;

use Oro\Bundle\LocaleBundle\Entity\Localization;

class LoadLocalizationDemoData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var array
     */
    protected $localizations = [
        ['language' => 'en_US', 'formatting' => 'en_US', 'parent' => null],
        ['language' => 'en_CA', 'formatting' => 'en_CA', 'parent' => 'en_US'],
        ['language' => 'en_GB', 'formatting' => 'en_GB', 'parent' => 'en_US'],
        ['language' => 'en_AU', 'formatting' => 'en_AU', 'parent' => 'en_US'],
        ['language' => 'es_MX', 'formatting' => 'es_MX', 'parent' => 'en_US'],
        ['language' => 'fr_CA', 'formatting' => 'fr_CA', 'parent' => 'en_CA'],
        ['language' => 'fr', 'formatting' => 'fr_FR', 'parent' => 'fr_CA'],
        ['language' => 'de', 'formatting' => 'de_DE', 'parent' => 'en_US'],
    ];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $registry = [];
        foreach ($this->localizations as $item) {
            $code = $item['language'];
            $name = $this->getLocaleNameByCode($code);

            $localization = new Localization();
            $localization
                ->setLanguageCode($item['language'])
                ->setFormattingCode($item['formatting'])
                ->setName($name);

            if ($item['parent']) {
                $parentCode = $item['parent'];

                $localization->setParent($registry[$parentCode]);
            }
            $registry[$code] = $localization;

            $manager->persist($localization);

            $this->addReference($code, $localization);
        }

        $manager->flush();
    }

    /**
     * @param string $code
     * @return string
     */
    protected function getLocaleNameByCode($code)
    {
        return Intl::getLocaleBundle()->getLocaleName($code, $this->container->get('oro_locale.settings')->getLocale());
    }
}
