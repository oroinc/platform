<?php

namespace Oro\Bundle\TranslationBundle\Form\TranslationForm;

use Symfony\Component\Form\FormTypeGuesserInterface;

/**
 * Handles logic for configuring options for form types
 */
interface TranslationFormInterface
{
    /**
     * @param string $class
     * @param array $options
     * @return array
     */
    public function getChildrenOptions($class, array $options);

    /**
     * @param FormTypeGuesserInterface $guesser
     * @param string $class
     * @param string $property
     * @param array $options
     * @return array
     */
    public function guessMissingChildOptions($guesser, $class, $property, array $options);

    /**
     * @param string $class
     * @return string
     */
    public function getTranslationClass($class);
}
