<?php

namespace Oro\Bundle\EntityBundle\Form\Guesser;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use Oro\Bundle\FormBundle\Guesser\FormBuildData;
use Oro\Bundle\FormBundle\Guesser\FormGuesserInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;

abstract class AbstractFormGuesser implements FormGuesserInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var ConfigProviderInterface
     */
    protected $entityConfigProvider;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param ConfigProviderInterface $entityConfigProvider
     */
    public function __construct(ManagerRegistry $managerRegistry, ConfigProviderInterface $entityConfigProvider)
    {
        $this->managerRegistry = $managerRegistry;
        $this->entityConfigProvider = $entityConfigProvider;
    }

    /**
     * @param string $class
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
     * @param array $formOptions
     * @return FormBuildData
     */
    protected function createFormBuildData($formType, array $formOptions = array())
    {
        return new FormBuildData($formType, $formOptions);
    }

    /**
     * @param array $options
     * @param string $class
     * @param string|null $field
     * @param bool $multiple
     * @return array
     */
    protected function addLabelOption(array $options, $class, $field = null, $multiple = false)
    {
        if (array_key_exists('label', $options) || !$this->entityConfigProvider->hasConfig($class, $field)) {
            return $options;
        }

        $entityConfig = $this->entityConfigProvider->getConfig($class, $field);
        $labelOption = $multiple ? 'plural_label' : 'label';
        if ($entityConfig->has($labelOption)) {
            $options['label'] = $entityConfig->get($labelOption);
        }

        return $options;
    }
}
