<?php

namespace Oro\Bundle\EmailBundle\Form\DataMapper;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateTranslationResolver;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Adding default localization for fields from the EmailTemplate entity
 */
class EmailTemplateDataMapper implements DataMapperInterface
{
    public function __construct(
        private readonly EmailTemplateTranslationResolver $emailTemplateTranslationResolver,
        private readonly FileManager $fileManager,
        private readonly ?DataMapperInterface $inner = null
    ) {
    }

    #[\Override]
    public function mapDataToForms($viewData, $forms): void
    {
        if ($viewData === null) {
            return;
        }

        /** @var EmailTemplate $viewData */
        $this->assertViewDataType($viewData);

        $innerMapperForms = [];
        foreach ($forms as $form) {
            if ($form->getName() === 'translations') {
                $emailTemplateTranslation = new EmailTemplateTranslation();
                $defaultEmailTemplateTranslation = $this->resolveDefaultTranslations(
                    $emailTemplateTranslation,
                    $viewData
                );

                $data = [
                    'default' => $defaultEmailTemplateTranslation,
                ];

                $emailTemplateTranslations = [];
                foreach ($viewData->getTranslations() as $emailTemplateTranslation) {
                    $emailTemplateTranslations[$emailTemplateTranslation->getLocalization()?->getId()] =
                        $emailTemplateTranslation;
                }

                foreach ($form->all() as $key => $emailTemplateTranslationForm) {
                    if ($key === 'default') {
                        continue;
                    }

                    /** @var EmailTemplateTranslation $emailTemplateTranslation */
                    $emailTemplateTranslation = $emailTemplateTranslations[$key] ??
                        $emailTemplateTranslationForm->getViewData();

                    $emailTemplateTranslation->setTemplate($viewData);

                    $this->resolveFallbackTranslations($emailTemplateTranslation, $viewData);

                    $data[$key] = $emailTemplateTranslation;
                }

                $form->setData($data);
            } else {
                $innerMapperForms[] = $form;
            }
        }

        // Fallback to inner data mapper with not mapped fields
        $this->inner?->mapDataToForms($viewData, new \ArrayIterator($innerMapperForms));
    }

    private function resolveDefaultTranslations(
        EmailTemplateTranslation $emailTemplateTranslation,
        EmailTemplate $emailTemplate
    ): EmailTemplateTranslation {
        $emailTemplateTranslation
            ->setSubject($emailTemplate->getSubject())
            ->setSubjectFallback(false)
            ->setContent($emailTemplate->getContent())
            ->setContentFallback(false)
            ->setAttachmentsFallback(false)
            ->setTemplate($emailTemplate);

        foreach ($emailTemplate->getAttachments() as $emailTemplateAttachment) {
            $emailTemplateTranslation->addAttachment($emailTemplateAttachment);
            $emailTemplateAttachment->setTranslation(null);
        }

        return $emailTemplateTranslation;
    }

    private function resolveFallbackTranslations(
        EmailTemplateTranslation $emailTemplateTranslation,
        EmailTemplate $emailTemplate
    ): void {
        $localization = $emailTemplateTranslation->getLocalization();

        if ($emailTemplateTranslation->isSubjectFallback()) {
            $subject = $this->emailTemplateTranslationResolver
                ->resolveTranslation($emailTemplate, 'subject', $localization);

            $emailTemplateTranslation->setSubject($subject);
        }

        if ($emailTemplateTranslation->isContentFallback()) {
            $subject = $this->emailTemplateTranslationResolver
                ->resolveTranslation($emailTemplate, 'content', $localization);

            $emailTemplateTranslation->setContent($subject);
        }

        if ($emailTemplateTranslation->isAttachmentsFallback()) {
            $emailTemplateAttachments = $this->emailTemplateTranslationResolver
                ->resolveTranslation($emailTemplate, 'attachments', $localization);

            foreach ($emailTemplateAttachments as $emailTemplateAttachment) {
                $emailTemplateAttachmentEntity = new EmailTemplateAttachment();
                $emailTemplateAttachmentEntity
                    ->setFile($emailTemplateAttachment->getFile())
                    ->setFilePlaceholder($emailTemplateAttachment->getFilePlaceholder());
            }
        }
    }

    #[\Override]
    public function mapFormsToData($forms, &$viewData): void
    {
        if ($viewData === null) {
            return;
        }

        /** @var EmailTemplate $viewData */
        $this->assertViewDataType($viewData);

        $innerMapperForms = [];
        foreach ($forms as $form) {
            if ($form->getName() === 'translations') {
                $this->mapFormToTranslations($form->getData(), $viewData);
            } else {
                $innerMapperForms[] = $form;
            }
        }

        // Fallback to inner data mapper with not mapped fields
        $this->inner?->mapFormsToData(new \ArrayIterator($innerMapperForms), $viewData);
    }

    /**
     * @param EmailTemplateTranslation[] $data
     * @param EmailTemplate $viewData
     */
    private function mapFormToTranslations(array $data, EmailTemplate $viewData): void
    {
        // Process default translation.
        $this->processDefaultTranslation($viewData, $data);
        unset($data['default']);

        // Process existing translations.
        foreach ($viewData->getTranslations() as $templateTranslation) {
            $this->processExistingTranslation($templateTranslation, $data);

            unset($data[$templateTranslation->getLocalization()->getId()]);
        }

        // Process new translations.
        foreach ($data as $newTemplateLocalization) {
            $viewData->addTranslation($newTemplateLocalization);
        }
    }

    private function processDefaultTranslation(EmailTemplate $viewData, array $data): void
    {
        $viewData
            ->setSubject($data['default']?->getSubject())
            ->setContent($data['default']?->getContent());

        if ($data['default'] !== null) {
            /**
             * @var EmailTemplateAttachment $emailTemplateAttachment
             */
            foreach ($data['default']->getAttachments() as $key => $emailTemplateAttachment) {
                if ($viewData->getAttachments()->containsKey($key)) {
                    /** @var EmailTemplateAttachment $originalEmailAttachment */
                    $originalEmailAttachment = $viewData->getAttachments()->get($key);
                    $originalEmailAttachment->setFile($emailTemplateAttachment->getFile());
                    $originalEmailAttachment->setFilePlaceholder($emailTemplateAttachment->getFilePlaceholder());
                    $originalEmailAttachment->setTemplate($viewData);
                } else {
                    $viewData->addAttachment($emailTemplateAttachment);
                }
            }
        }

        foreach ($viewData->getAttachments() as $key => $emailTemplateAttachment) {
            if (!$data['default']?->getAttachments()->containsKey($key)) {
                $viewData->removeAttachment($emailTemplateAttachment);
            }
        }
    }

    private function processExistingTranslation(EmailTemplateTranslation $templateTranslation, array $data): void
    {
        $localizationId = $templateTranslation->getLocalization()->getId();
        if (!isset($data[$localizationId])) {
            $templateTranslation
                ->setSubject(null)
                ->setSubjectFallback(true)
                ->setContent(null)
                ->setContentFallback(true)
                ->setAttachmentsFallback(true);

            $templateTranslation->getAttachments()->clear();
        } else {
            $templateTranslation
                ->setSubject($data[$localizationId]->getSubject())
                ->setSubjectFallback($data[$localizationId]->isSubjectFallback())
                ->setContent($data[$localizationId]->getContent())
                ->setContentFallback($data[$localizationId]->isContentFallback())
                ->setAttachmentsFallback($data[$localizationId]->isAttachmentsFallback());

            if ($data[$localizationId]->isAttachmentsFallback()) {
                $templateTranslation->getAttachments()->clear();
            } else {
                /**
                 * @var EmailTemplateAttachment $emailTemplateAttachment
                 */
                foreach ($data[$localizationId]->getAttachments() as $key => $emailTemplateAttachment) {
                    if ($templateTranslation->getAttachments()->containsKey($key)) {
                        /** @var EmailTemplateAttachment $originalEmailAttachment */
                        $originalEmailAttachment = $templateTranslation->getAttachments()->get($key);
                        $emailTemplateAttachmentFile = $emailTemplateAttachment->getFile();
                        if (
                            $emailTemplateAttachmentFile &&
                            !$emailTemplateAttachmentFile->getId() &&
                            !$emailTemplateAttachmentFile->getFile()
                        ) {
                            if ($emailTemplateAttachmentFile->isEmptyFile()) {
                                $emailTemplateAttachmentFile = null;
                            } else {
                                $emailTemplateAttachmentFile = $this->fileManager
                                    ->cloneFileEntity($emailTemplateAttachmentFile);
                            }
                        }

                        $originalEmailAttachment->setFile($emailTemplateAttachmentFile);
                        $originalEmailAttachment->setFilePlaceholder($emailTemplateAttachment->getFilePlaceholder());
                        $originalEmailAttachment->setTranslation($templateTranslation);
                    } else {
                        $templateTranslation->addAttachment($emailTemplateAttachment);
                    }
                }
            }
        }
    }

    /**
     * @throws UnexpectedTypeException
     */
    private function assertViewDataType($viewData): void
    {
        if (!$viewData instanceof EmailTemplate) {
            throw new UnexpectedTypeException($viewData, EmailTemplate::class);
        }
    }
}
