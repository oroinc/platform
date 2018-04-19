<?php

namespace Oro\Bundle\TranslationBundle\Form\TranslationForm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\FormTypeGuesserInterface;

/**
 * Handles logic for configuring options for form types
 */
abstract class AbstractTranslationForm implements TranslationFormInterface
{
    /**
     * @var FormTypeGuesserInterface
     */
    private $typeGuesser;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param string $class
     * @return array
     */
    abstract protected function getTranslatableFields($class);

    /**
     * @param FormRegistry $formRegistry
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(FormRegistry $formRegistry, ManagerRegistry $managerRegistry)
    {
        $this->typeGuesser = $formRegistry->getTypeGuesser();
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return ManagerRegistry
     */
    public function getManagerRegistry()
    {
        return $this->managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildrenOptions($class, array $options)
    {
        $childrenOptions = [];

        unset($options['inherit_data']);
        unset($options['translatable_class']);

        $children = array_unique(array_merge(array_keys($options['fields']), $this->getTranslatableFields($class)));

        // Custom options by field
        foreach ($children as $child) {
            $childOptions = (isset($options['fields'][$child]) ? $options['fields'][$child] : []) +
                ['required' => $options['required']];

            if (!isset($childOptions['display']) || $childOptions['display']) {
                $childOptions = $this->guessMissingChildOptions($this->typeGuesser, $class, $child, $childOptions);
                $this->fillChildrenOptions($childOptions, $child, $options, $childrenOptions);
            }
        }

        return $childrenOptions;
    }

    /**
     * @param array $childOptions
     * @param string $child
     * @param array $options
     * @param array $childrenOptions
     * @return array
     */
    private function fillChildrenOptions(array $childOptions, $child, array $options, array &$childrenOptions)
    {
        // Custom options by locale
        if (isset($childOptions['locale_options'])) {
            $localesChildOptions = $childOptions['locale_options'];
            unset($childOptions['locale_options']);

            foreach ($options['locales'] as $locale) {
                $localeChildOptions = isset($localesChildOptions[$locale]) ? $localesChildOptions[$locale] : [];
                if (!isset($localeChildOptions['display']) || $localeChildOptions['display']) {
                    $childrenOptions[$locale][$child] = $localeChildOptions + $childOptions;
                }
            }
            // General options for all locales
        } else {
            foreach ($options['locales'] as $locale) {
                $childrenOptions[$locale][$child] = $childOptions;
            }
        }

        return $childrenOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function guessMissingChildOptions($guesser, $class, $property, array $options)
    {
        if (!isset($options['field_type']) && ($typeGuess = $guesser->guessType($class, $property))) {
            $options['field_type'] = $typeGuess->getType();
        }

        if (!isset($options['attr']['maxlength']) && ($maxLengthGuess = $guesser->guessMaxLength($class, $property))) {
            $options['attr']['maxlength'] = $maxLengthGuess->getValue();
        }

        if (!isset($options['attr']['pattern']) && ($patternGuess = $guesser->guessPattern($class, $property))) {
            $options['attr']['pattern'] = $patternGuess->getValue();
        }

        return $options;
    }
}
