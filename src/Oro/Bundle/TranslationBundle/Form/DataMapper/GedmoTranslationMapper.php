<?php

namespace Oro\Bundle\TranslationBundle\Form\DataMapper;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Inspired by https://github.com/a2lix/TranslationFormBundle/tree/1.x
 * Converts form data and vise versa to supported format
 */
class GedmoTranslationMapper implements DataMapperInterface
{
    /**
     * {@inheritdoc}
     */
    public function mapDataToForms($data, $forms)
    {
        if (null === $data || [] === $data) {
            return;
        }

        $this->checkDataSupported($data);

        foreach ($forms as $translationsFieldsForm) {
            $locale = $translationsFieldsForm->getConfig()->getName();

            $tmpFormData = [];
            foreach ($data as $translation) {
                if ($locale === $translation->getLocale()) {
                    $tmpFormData[$translation->getField()] = $translation->getContent();
                }
            }
            $translationsFieldsForm->setData($tmpFormData);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormsToData($forms, &$data)
    {
        if (null === $data) {
            return;
        }

        $this->checkDataSupported($data);

        $newData = new ArrayCollection();

        foreach ($forms as $translationsFieldsForm) {
            $translationsFieldsConfig = $translationsFieldsForm->getConfig();
            $locale = $translationsFieldsConfig->getName();
            $translationClass = $translationsFieldsConfig->getOption('translation_class');

            foreach ($translationsFieldsForm->getData() as $field => $content) {
                $existingTranslation = $data ? $data->filter(function ($object) use ($locale, $field) {
                    return ($object && ($object->getLocale() === $locale) && ($object->getField() === $field));
                })->first() : null;

                if ($existingTranslation) {
                    $existingTranslation->setContent($content);
                    $newData->add($existingTranslation);
                } else {
                    $translation = new $translationClass();
                    $translation->setLocale($locale);
                    $translation->setField($field);
                    $translation->setContent($content);
                    $newData->add($translation);
                }
            }
        }

        $data = $newData;
    }

    /**
     * @param mixed $data
     * @throws UnexpectedTypeException
     */
    private function checkDataSupported($data)
    {
        if (!is_array($data) && !is_object($data)) {
            throw new UnexpectedTypeException($data, 'object, array or empty');
        }
    }
}
