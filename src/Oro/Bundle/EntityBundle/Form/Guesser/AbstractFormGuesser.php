<?php

namespace Oro\Bundle\EntityBundle\Form\Guesser;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;

/**
 * Provides common functionality for form type guessers that use Doctrine metadata and entity configuration.
 *
 * This base class provides access to the Doctrine manager registry and entity configuration provider,
 * along with helper methods for retrieving entity metadata and creating form type guesses.
 * Subclasses should implement specific guessing logic for form types, required status, max length, and patterns.
 */
abstract class AbstractFormGuesser implements FormTypeGuesserInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var ConfigProvider
     */
    protected $entityConfigProvider;

    public function __construct(ManagerRegistry $managerRegistry, ConfigProvider $entityConfigProvider)
    {
        $this->managerRegistry      = $managerRegistry;
        $this->entityConfigProvider = $entityConfigProvider;
    }

    #[\Override]
    public function guessRequired($class, $property): ?ValueGuess
    {
        return new ValueGuess(false, ValueGuess::LOW_CONFIDENCE);
    }

    #[\Override]
    public function guessMaxLength($class, $property): ?ValueGuess
    {
        return new ValueGuess(null, ValueGuess::LOW_CONFIDENCE);
    }

    #[\Override]
    public function guessPattern($class, $property): ?ValueGuess
    {
        return new ValueGuess(null, ValueGuess::LOW_CONFIDENCE);
    }

    /**
     * @param string $class
     *
     * @return ClassMetadata|null
     */
    protected function getMetadataForClass($class)
    {
        $entityManager = $this->managerRegistry->getManagerForClass($class);
        if (!$entityManager) {
            return null;
        }

        return $entityManager->getClassMetadata($class);
    }

    /**
     * @param string $formType
     * @param array  $formOptions
     * @param int    $confidence
     *
     * @return TypeGuess
     */
    protected function createTypeGuess(
        $formType,
        array $formOptions = array(),
        $confidence = TypeGuess::HIGH_CONFIDENCE
    ) {
        return new TypeGuess($formType, $formOptions, $confidence);
    }

    /**
     * @return TypeGuess
     */
    protected function createDefaultTypeGuess()
    {
        return new TypeGuess(TextType::class, array(), TypeGuess::LOW_CONFIDENCE);
    }

    /**
     * @param array       $options
     * @param string      $class
     * @param string|null $field
     * @param bool        $multiple
     *
     * @return array
     */
    protected function addLabelOption(array $options, $class, $field = null, $multiple = false)
    {
        if (array_key_exists('label', $options) || !$this->entityConfigProvider->hasConfig($class, $field)) {
            return $options;
        }

        $entityConfig = $this->entityConfigProvider->getConfig($class, $field);
        $labelOption  = $multiple ? 'plural_label' : 'label';
        if ($entityConfig->has($labelOption)) {
            $options['label'] = $entityConfig->get($labelOption);
        }

        return $options;
    }
}
