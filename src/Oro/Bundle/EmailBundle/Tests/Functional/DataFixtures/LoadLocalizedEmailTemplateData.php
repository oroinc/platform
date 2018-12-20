<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadLocalizedEmailTemplateData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    const DEFAULT_SUBJECT = 'Default subject';
    const DEFAULT_CONTENT = 'Default content';
    const FRENCH_LOCALIZED_SUBJECT = 'French subject';
    const FRENCH_LOCALIZED_CONTENT = 'French content';

    /** @var ContainerInterface */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadUserData::class];
    }

    /**
     * {@inheritdoc}
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
        $owner = $this->getReference('simple_user');

        $frenchEmailTemplate = new EmailTemplate('french_localized_template', self::DEFAULT_CONTENT);
        $frenchEmailTemplate->setSubject(self::DEFAULT_SUBJECT);
        $frenchEmailTemplate->setVisible(false);
        $frenchEmailTemplate->setEntityName(User::class);
        $frenchEmailTemplate->setOrganization($owner->getOrganization());

        $noEntityEmailTemplate = new EmailTemplate('no_entity_localized_template', self::DEFAULT_CONTENT);
        $noEntityEmailTemplate->setSubject(self::DEFAULT_SUBJECT);
        $noEntityEmailTemplate->setVisible(false);
        $noEntityEmailTemplate->setOrganization($owner->getOrganization());

        $manager->persist($frenchEmailTemplate);
        $manager->persist($noEntityEmailTemplate);
        $manager->flush();

        // Saving french version
        /** @var TranslatableListener $translatableListener */
        $translatableListener = $this->container->get('stof_doctrine_extensions.listener.translatable');
        $translatableListener->setTranslatableLocale('fr_FR');

        $frenchEmailTemplate->setSubject(self::FRENCH_LOCALIZED_SUBJECT);
        $frenchEmailTemplate->setContent(self::FRENCH_LOCALIZED_CONTENT);

        $noEntityEmailTemplate->setSubject(self::FRENCH_LOCALIZED_SUBJECT);
        $noEntityEmailTemplate->setContent(self::FRENCH_LOCALIZED_CONTENT);

        $manager->flush();
    }
}
