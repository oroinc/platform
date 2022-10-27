<?php

namespace Oro\Bundle\EmailBundle\Form\DataMapper;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Adding default localization for fields from the EmailTemplate entity
 */
class LocalizationAwareEmailTemplateDataMapper implements DataMapperInterface
{
    /** @var DataMapperInterface|null */
    private $inner;

    /**
     * @param DataMapperInterface|null $inner Original data mapper
     */
    public function __construct(DataMapperInterface $inner = null)
    {
        $this->inner = $inner;
    }

    /**
     * {@inheritdoc}
     */
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
                $entity = new EmailTemplateTranslation();
                $entity
                    ->setSubject($viewData->getSubject())
                    ->setContent($viewData->getContent());

                $data = ['default' => $entity];

                /** @var EmailTemplateTranslation $emailTemplateTranslation */
                foreach ($viewData->getTranslations() as $emailTemplateTranslation) {
                    $data[$emailTemplateTranslation->getLocalization()->getId()] = $emailTemplateTranslation;
                }

                $form->setData($data);
            } else {
                $innerMapperForms[] = $form;
            }
        }

        // Fallback to inner data mapper with not mapped fields
        if ($this->inner) {
            $this->inner->mapDataToForms($viewData, new \ArrayIterator($innerMapperForms));
        }
    }

    /**
     * {@inheritdoc}
     */
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
        if ($this->inner) {
            $this->inner->mapFormsToData(new \ArrayIterator($innerMapperForms), $viewData);
        }
    }

    /**
     * @param EmailTemplateTranslation[] $data
     * @param EmailTemplate $viewData
     */
    private function mapFormToTranslations(array $data, EmailTemplate $viewData): void
    {
        // Process default template localization
        $viewData
            ->setSubject($data['default'] ? $data['default']->getSubject() : null)
            ->setContent($data['default'] ? $data['default']->getContent() : null);
        unset($data['default']);

        // Process existing translations
        foreach ($viewData->getTranslations() as $templateTranslation) {
            $localizationId = $templateTranslation->getLocalization()->getId();
            if (!isset($data[$localizationId])) {
                $templateTranslation
                    ->setSubject(null)
                    ->setSubjectFallback(true)
                    ->setContent(null)
                    ->setContentFallback(true);
            } else {
                $templateTranslation
                    ->setSubject($data[$localizationId]->getSubject())
                    ->setSubjectFallback($data[$localizationId]->isSubjectFallback())
                    ->setContent($data[$localizationId]->getContent())
                    ->setContentFallback($data[$localizationId]->isContentFallback());

                unset($data[$localizationId]);
            }
        }

        // Process new translations
        foreach ($data as $newTemplateLocalization) {
            $viewData->addTranslation($newTemplateLocalization);
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
