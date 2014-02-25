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
    public function guess($class, $field = null)
    {
        $metadata = $this->getMetadataForClass($class);
        if (!$metadata) {
            return null;
        }

        if (!$this->formConfigProvider->hasConfig($class, $field)) {
            return null;
        }

        $formConfig = $this->formConfigProvider->getConfig($class, $field);
        if (!$formConfig->has('form_type')) {
            // try to find form config for target class
            if ($field && $metadata->hasAssociation($field) && $metadata->isSingleValuedAssociation($field)) {
                return $this->guess($metadata->getAssociationTargetClass($field));
            }

            return null;
        }

        $formType = $formConfig->get('form_type');
        $formOptions = $formConfig->has('form_options') ? $formConfig->get('form_options') : array();
        $formOptions = $this->addLabelOption($formOptions, $class, $field);

        return $this->createFormBuildData($formType, $formOptions);
    }
}
