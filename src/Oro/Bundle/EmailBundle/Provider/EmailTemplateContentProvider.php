<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateCompilationException;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateNotFoundException;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Provides compiled email template information ready to be sent via email.
 */
class EmailTemplateContentProvider
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var EmailRenderer */
    private $emailRenderer;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ManagerRegistry $doctrine,
        EmailRenderer $emailRenderer,
        PropertyAccessor $propertyAccessor,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->emailRenderer = $emailRenderer;
        $this->propertyAccessor = $propertyAccessor;
        $this->logger = $logger;
    }

    /**
     * Get localized email template
     */
    public function getTemplateContent(
        EmailTemplateCriteria $criteria,
        Localization $localization,
        array $templateParams
    ): EmailTemplateModel {
        $repository = $this->doctrine->getRepository(EmailTemplate::class);

        try {
            /** @var EmailTemplate $emailTemplate */
            $emailTemplateEntity = $repository->findWithLocalizations($criteria);
        } catch (NonUniqueResultException | NoResultException $exception) {
            $this->logger->error(
                'Could not find unique email template for the given criteria',
                ['exception' => $exception, 'criteria' => $criteria]
            );

            throw new EmailTemplateNotFoundException($criteria);
        }

        $emailTemplateModel = $this->getLocalizedModel($emailTemplateEntity, $localization);

        try {
            [$subject, $content] = $this->emailRenderer->compileMessage($emailTemplateModel, $templateParams);
            $emailTemplateModel
                ->setSubject($subject)
                ->setContent($content);
        } catch (\Twig_Error $exception) {
            $this->logger->error(
                sprintf(
                    'Rendering of email template "%s" failed. %s',
                    $emailTemplateModel->getSubject(),
                    $exception->getMessage()
                ),
                ['exception' => $exception]
            );

            throw new EmailTemplateCompilationException($criteria);
        }

        return $emailTemplateModel;
    }

    public function getLocalizedModel(EmailTemplate $entity, Localization $localization): EmailTemplateModel
    {
        $model = new EmailTemplateModel();

        $templateIndex = [];

        foreach ($entity->getTranslations() as $templateTranslation) {
            $templateIndex[$templateTranslation->getLocalization()->getId()] = $templateTranslation;
        }

        $this->populateAttribute($templateIndex, $localization, $model, $entity, 'subject');
        $this->populateAttribute($templateIndex, $localization, $model, $entity, 'content');

        $model->setType(
            $entity->getType() === EmailTemplate::TYPE_HTML
                ? EmailTemplateModel::CONTENT_TYPE_HTML
                : EmailTemplateModel::CONTENT_TYPE_TEXT
        );

        return $model;
    }

    /**
     * Localize model attribute
     *
     * Finding the right template for the localization tree based on the fallback attribute.
     * When not exist template or specified fallback for localization without a parent
     * used default attribute value from entity.
     */
    private function populateAttribute(
        array $templateIndex,
        Localization $localization,
        EmailTemplateModel $model,
        EmailTemplate $entity,
        string $attribute
    ): void {
        $attributeFallback = $attribute . 'Fallback';

        while ($currentTemplate = $this->findTemplate($templateIndex, $localization)) {
            // For current attribute not enabled fallback to parent localizations
            if (!$this->propertyAccessor->getValue($currentTemplate, $attributeFallback)) {
                $this->propertyAccessor->setValue(
                    $model,
                    $attribute,
                    $this->propertyAccessor->getValue($currentTemplate, $attribute)
                );
                return;
            }

            // Find next available localized template by localization tree
            $localization = $currentTemplate->getLocalization()->getParentLocalization();
        }

        // Fallback to default when template for localization not found
        $this->propertyAccessor->setValue(
            $model,
            $attribute,
            $this->propertyAccessor->getValue($entity, $attribute)
        );
    }

    /**
     * @param array $templateIndex
     * @param Localization $localization
     * @return EmailTemplateTranslation|null
     */
    private function findTemplate(array &$templateIndex, ?Localization $localization): ?EmailTemplateTranslation
    {
        while ($localization) {
            if (isset($templateIndex[$localization->getId()])) {
                $template = $templateIndex[$localization->getId()];

                // Fix possible deadlock on a looped localization tree
                unset($templateIndex[$localization->getId()]);

                return $template;
            }

            $localization = $localization->getParentLocalization();
        }

        return null;
    }
}
