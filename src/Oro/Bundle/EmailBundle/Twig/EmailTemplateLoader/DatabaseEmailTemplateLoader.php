<?php

namespace Oro\Bundle\EmailBundle\Twig\EmailTemplateLoader;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Provider\TranslatedEmailTemplateProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Twig\Error\LoaderError;
use Twig\Source;

/**
 * Loads an email template from database taking into account parsed parameters.
 *
 * Examples of the supported email template names:
 * @db:/sample_template_name
 * @db:entityName=Acme\Bundle\Entity\SampleEntity/sample_template_name
 * @db:entityName=Acme\Bundle\Entity\SampleEntity&localization=42/sample_template_name
 */
class DatabaseEmailTemplateLoader implements EmailTemplateLoaderInterface
{
    use EmailTemplateLoaderParsingTrait;

    private ManagerRegistry $managerRegistry;

    private TranslatedEmailTemplateProvider $translatedEmailTemplateProvider;

    public function __construct(
        ManagerRegistry $managerRegistry,
        TranslatedEmailTemplateProvider $translatedEmailTemplateProvider,
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->translatedEmailTemplateProvider = $translatedEmailTemplateProvider;
    }

    public function exists($name): bool
    {
        [$emailTemplateCriteria, $templateContext] = $this->createEmailTemplateCriteriaAndTemplateContext($name);
        if ($emailTemplateCriteria === null) {
            return false;
        }

        return $this->managerRegistry
            ->getRepository(EmailTemplateEntity::class)
            ->isExist($emailTemplateCriteria, $templateContext);
    }

    private function createEmailTemplateCriteriaAndTemplateContext(string $name): array
    {
        if ($this->isNamespaced($name)) {
            [$emailTemplateCriteria, $templateContext] = $this->parseName($name, 'db');
            if (!$emailTemplateCriteria) {
                return [null, []];
            }
        } else {
            $emailTemplateCriteria = new EmailTemplateCriteria($name);
            $templateContext = [];
        }

        if (isset($templateContext['localization'])) {
            $entityManager = $this->managerRegistry->getManagerForClass(Localization::class);
            $templateContext['localization'] = $entityManager->getReference(
                Localization::class,
                $templateContext['localization']
            );
        }

        return [$emailTemplateCriteria, $templateContext];
    }

    public function getCacheKey($name): string
    {
        return $name;
    }

    public function isFresh($name, $time): bool
    {
        return true;
    }

    public function getSourceContext($name): Source
    {
        $emailTemplate = $this->getEmailTemplate($name);

        return new Source($emailTemplate->getContent(), $name);
    }

    public function getEmailTemplate(string $name): EmailTemplateModel
    {
        [$emailTemplateCriteria, $templateContext] = $this->createEmailTemplateCriteriaAndTemplateContext($name);
        if ($emailTemplateCriteria === null) {
            throw new LoaderError('Failed to create an email template criteria for "' . $name . '"');
        }

        $emailTemplateEntity = $this->managerRegistry
            ->getRepository(EmailTemplateEntity::class)
            ->findWithLocalizations($emailTemplateCriteria, $templateContext);

        if ($emailTemplateEntity === null) {
            throw new LoaderError('Failed to find email template "' . $name . '"');
        }

        return $this->translatedEmailTemplateProvider->getTranslatedEmailTemplate(
            $emailTemplateEntity,
            $templateContext['localization'] ?? null
        );
    }
}
