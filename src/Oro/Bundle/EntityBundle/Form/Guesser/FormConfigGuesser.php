<?php

namespace Oro\Bundle\EntityBundle\Form\Guesser;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;

class FormConfigGuesser extends AbstractFormGuesser
{
    /**
     * @var ConfigProviderInterface
     */
    protected $formConfigProvider;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param ConfigProviderInterface $entityConfigProvider
     * @param ConfigProviderInterface $formConfigProvider
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ConfigProviderInterface $entityConfigProvider,
        ConfigProviderInterface $formConfigProvider
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
        if (!$metadata) {
            return $this->createDefaultTypeGuess();
        }

        if (!$this->formConfigProvider->hasConfig($class, $property)) {
            return $this->createDefaultTypeGuess();
        }

        $formConfig = $this->formConfigProvider->getConfig($class, $property);
        if (!$formConfig->has('form_type')) {
            // try to find form config for target class
            if ($property && $metadata->hasAssociation($property) && $metadata->isSingleValuedAssociation($property)) {
                return $this->guessType($metadata->getAssociationTargetClass($property), null);
            }

            return $this->createDefaultTypeGuess();
        }

        $formType = $formConfig->get('form_type');
        $formOptions = $formConfig->has('form_options') ? $formConfig->get('form_options') : array();
        $formOptions = $this->addLabelOption($formOptions, $class, $property);

        return $this->createTypeGuess($formType, $formOptions);
    }
}
