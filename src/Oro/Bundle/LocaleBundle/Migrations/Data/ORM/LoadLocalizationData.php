<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Intl\Intl;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class LoadLocalizationData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /* @var $localeSettings LocaleSettings */
        $localeSettings = $this->container->get('oro_locale.settings');
        $code = $localeSettings->getLocale();

        $localization = new Localization();
        $localization->setName(
            Intl::getLocaleBundle()->getLocaleName($localeSettings->getLanguage(), $code)
        );
        $localization
            ->setLanguageCode($code)
            ->setFormattingCode($code);

        $manager->persist($localization);
        /* @var $manager EntityManager */
        $manager->flush($localization);

        $this->addReference('default_localization', $localization);
    }
}
