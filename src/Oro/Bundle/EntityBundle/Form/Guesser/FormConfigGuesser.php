<?php

namespace Oro\Bundle\EntityBundle\Form\Guesser;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\Guess\TypeGuess;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class FormConfigGuesser extends AbstractFormGuesser
{
    /**
     * @var ConfigProvider
     */
    protected $formConfigProvider;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param ConfigProvider  $entityConfigProvider
     * @param ConfigProvider  $formConfigProvider
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $formConfigProvider
    ) {
        parent::__construct($managerRegistry, $entityConfigProvider);
        $this->formConfigProvider = $formConfigProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function guessType($class, $property)
    {
        $metadata = $this->getMetadataForClass($class);
        if (!$metadata || !$this->formConfigProvider->hasConfig($class, $property)) {
            return $this->createDefaultTypeGuess();
        }

        $formConfig = $this->formConfigProvider->getConfig($class, $property);

        $isSingleValuedAssoc = $property && $metadata->hasAssociation($property) &&
            $metadata->isSingleValuedAssociation($property);
        $hasNoFormType       = !$formConfig->has('form_type');

        if ($hasNoFormType && $isSingleValuedAssoc) {
            // try to find form config for target class
            $guess = $this->guessType($metadata->getAssociationTargetClass($property), null);
        } elseif ($hasNoFormType) {
            $guess = $this->createDefaultTypeGuess();
        } else {
            $guess = $this->getTypeGuess($formConfig, $class, $property);
        }

        return $guess;
    }

    /**
     * @param ConfigInterface $formConfig
     * @param string          $class
     * @param string          $property
     *
     * @return TypeGuess
     */
    protected function getTypeGuess(ConfigInterface $formConfig, $class, $property)
    {
        $formType    = $formConfig->get('form_type');
        $formOptions = $formConfig->get('form_options', false, array());
        $formOptions = $this->addLabelOption($formOptions, $class, $property);

        return $this->createTypeGuess($formType, $formOptions);
    }
}
