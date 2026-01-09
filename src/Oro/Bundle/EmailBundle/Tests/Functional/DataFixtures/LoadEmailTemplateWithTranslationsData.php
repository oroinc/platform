<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class LoadEmailTemplateWithTranslationsData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $this->removeDefaultTemplates($manager);
        $this->leadEmailTemplateWithTranslation($manager);
    }

    #[\Override]
    public function getDependencies()
    {
        return [LoadLocalizationData::class];
    }

    private function removeDefaultTemplates(ObjectManager $manager): void
    {
        $templates = $manager->getRepository(EmailTemplate::class)->findAll();
        foreach ($templates as $template) {
            $manager->remove($template);
        }

        $manager->flush();
    }

    public function leadEmailTemplateWithTranslation(ObjectManager $manager): void
    {
        $emailTemplate = new EmailTemplate('default_template');
        $emailTemplate
            ->setSubject('Default Subject')
            ->setContent('Default Content');

        $emailTemplateTranslation = new EmailTemplateTranslation();
        $emailTemplateTranslation
            ->setLocalization($this->getReference('en_CA'))
            ->setSubject('CA Subject')
            ->setContent('CA Content')
            ->setContentFallback(false)
            ->setTemplate($emailTemplate);

        $manager->persist($emailTemplateTranslation);
        $manager->persist($emailTemplate);
        $manager->flush();
    }
}
