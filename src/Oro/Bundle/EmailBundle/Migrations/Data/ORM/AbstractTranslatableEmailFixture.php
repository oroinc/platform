<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Finder\Finder;

abstract class AbstractTranslatableEmailFixture extends AbstractEmailFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /**
     * @var LocalizationManager
     */
    protected $localizationManager;

    /**
     * @var string
     */
    protected $defLocalizationCode;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->localizationManager = $this->container->get('oro_locale.manager.localization');

        /* Translation listener is not using default localization.. and if we set localization manually it saves
        correctly, but will be be wrongly displayed. 
        $this->defLocalizationCode = $this->localizationManager->getDefaultLocalization()->getLanguageCode();*/
        $this->defLocalizationCode = $this->container->get('stof_doctrine_extensions.listener.translatable')
            ->getDefaultLocale();

        $emailTemplates = $this->getEmailTemplatesList($this->getEmailsDir());

        foreach ($emailTemplates as $name => $locEmailTemplate) {
            $this->loadTemplateWithTranslations($manager, $name, $locEmailTemplate);
        }

        $manager->flush();
    }

    /**
     * Get a list of all email templates in the directory with their localized variations
     *
     * @param string $dir
     * @return array
     */
    public function getEmailTemplatesList($dir)
    {
        if (is_dir($dir)) {
            $finder = new Finder();
            $files = $finder->files()->in($dir);
        } else {
            $files = array();
        }

        $templates = array();

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($files as $file) {
            $fileParts = explode('.', $file->getFilename(), 3);

            $format = 'html';
            if (preg_match('#\.(html|txt)(\.twig)?#', $file->getFilename(), $match)) {
                $format = $match[1];
            }

            $name = $fileParts[0];
            $langCode = $fileParts[1];

            $templates[$name][$langCode] = [
                'path' => $file->getPath().DIRECTORY_SEPARATOR.$file->getFilename(),
                'format' => $format,
                'langCode' => $langCode,
            ];
        }

        return $templates;
    }

    /**
     *
     * @param ObjectManager $manager
     * @param string $name
     * @param array $locEmailTemplate
     */
    protected function loadTemplateWithTranslations(ObjectManager $manager, $name, array $locEmailTemplate)
    {
        $entityName = null;

        foreach ($locEmailTemplate as $locale => $emailTemplate) {
            $locEmailTemplate[$locale]['raw'] = file_get_contents($emailTemplate['path']);
            $parsedTemplate = EmailTemplate::parseContent($locEmailTemplate[$locale]['raw']);

            if (empty($parsedTemplate['params']['name'])) {
                $parsedTemplate['params']['name'] = $name;
            }

            if (empty($entityName) && isset($parsedTemplate['params']['entityName'])) {
                $entityName = $parsedTemplate['params']['entityName'];
            }

            $locEmailTemplate[$locale]['parsed'] = $parsedTemplate;
        }

        $existingTemplate = $this->findTemplate($manager, $name, $entityName);

        $this->persistTemplate($manager, $name, $locEmailTemplate, $existingTemplate);
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param array $locEmailTemplate
     * @param EmailTemplate $existingTemplate
     */
    protected function persistTemplate(ObjectManager $manager, $name, $locEmailTemplate, $existingTemplate = null)
    {
        if (isset($locEmailTemplate[$this->defLocalizationCode])) {
            $defaultLanguageTemp = $locEmailTemplate[$this->defLocalizationCode];
        } else {
            $defaultLanguageTemp = reset($locEmailTemplate);
        }

        if (empty($existingTemplate)) {
            $emailTemplate = new EmailTemplate($name, $defaultLanguageTemp['raw'], $defaultLanguageTemp['format']);
            $emailTemplate->setOwner($this->getAdminUser($manager))
                ->setOrganization($this->getOrganization($manager))
                ->setIsEditable(true);
            /* related to default localization bug mentioned above
            $this->container->get('stof_doctrine_extensions.listener.translatable')
                ->setDefaultLocale($this->defLocalizationCode)*/;
        } else {
            /** @var EmailTemplate $emailTemplate */
            $emailTemplate = $existingTemplate;
            $this->updateExistingTemplate($emailTemplate, $defaultLanguageTemp['parsed']);
        }

        $emailTemplate->setLocale($this->defLocalizationCode);
        $translations = $emailTemplate->getTranslations();
        $activeLanguageCodes = $this->getLanguageCodes();

        foreach ($locEmailTemplate as $locale => $templateData) {
            if (in_array($locale, $activeLanguageCodes) && $locale != $this->defLocalizationCode) {
                $translation = $this->getOrCreateTranslation($translations, 'content', $locale)
                    ->setContent($templateData['parsed']['content']);
                $translations->add($translation);

                $translation = $this->getOrCreateTranslation($translations, 'subject', $locale)
                    ->setContent($templateData['parsed']['params']['subject']);
                $translations->add($translation);
            }
        }

        $emailTemplate->setTranslations($translations);

        $manager->persist($emailTemplate);
    }

    /**
     * Find template by its name and entity name
     *
     * @return EmailTemplate|null
     */
    protected function findTemplate(ObjectManager $manager, $name, $entityName)
    {
        if (empty($name)) {
            return null;
        }

        return $manager->getRepository(EmailTemplate::class)->findOneBy([
            'name' => $name,
            'entityName' => $entityName,
        ]);
    }

    /**
     * Get language codes of all active localizations
     *
     * @return array
     */
    protected function getLanguageCodes()
    {
        $allLocalizations = $this->localizationManager->getLocalizations();
        $languageCodes = [];

        foreach ($allLocalizations as $localization) {
            $langcode = $localization->getLanguageCode();
            $languageCodes[] = $langcode;
        }

        return $languageCodes;
    }

    /**
     * Get existing translation or create a new one
     *
     * @param ArrayCollection|PersistentCollection $translations
     * @param string $field
     * @param string $locale
     *
     * @return EmailTemplateTranslation
     */
    protected function getOrCreateTranslation(&$translations, string $field, string $locale)
    {
        foreach ($translations as $translation) {
            if ($translation->getLocale() == $locale && $translation->getField() == $field) {
                $translations->removeElement($translation);

                return $translation;
            }
        }

        return (new EmailTemplateTranslation())->setLocale($locale)->setField($field);
    }
}
