<?php

namespace Oro\Bundle\WorkflowBundle\Validator;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\AbstractLoader;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class WorkflowValidationLoader extends AbstractLoader
{
    /** @var ConfigProvider */
    protected $configProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigProvider $configProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigProvider $configProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(ClassMetadata $metadata)
    {
        $className = $metadata->getClassName();

        if (!$this->configProvider->hasConfig($className)){
            return false;
        }

        $config = $this->configProvider->getConfig($className);

        if ($config->get('active_workflow', false, false)) {
            $metadata->addConstraint(
                $this->newConstraint('Oro\Bundle\WorkflowBundle\Validator\Constraints\WorkflowEntity')
            );

            return true;
        }

        return false;
    }
}
