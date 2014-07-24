<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Util\Inflector;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class EntityVariablesProvider implements VariablesProviderInterface
{
    /** @var ConfigProvider */
    protected $emailConfigProvider;

    /**
     * @param ConfigProvider $emailConfigProvider
     */
    public function __construct(ConfigProvider $emailConfigProvider)
    {
        $this->emailConfigProvider = $emailConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateVariables(array $context = [])
    {
        if (!isset($context['entityName'])) {
            return [];
        }

        $entityClassName = $context['entityName'];
        if (!$this->emailConfigProvider->hasConfig($entityClassName)) {
            return [];
        }

        $fields = [];

        $reflClass = new \ReflectionClass($entityClassName);
        $fieldConfigs = $this->emailConfigProvider->getConfigs($entityClassName);
        foreach ($fieldConfigs as $fieldConfig) {
            if (!$fieldConfig->is('available_in_template')) {
                continue;
            }

            $fieldName = $fieldConfig->getId()->getFieldName();
            if ($reflClass->hasProperty($fieldName) && $reflClass->pro($fieldName)

            method_exists()

            $varName = Inflector::camelize($fieldConfig->getId()->getFieldName());
        }
        $fields = array_values(
            array_map(
                function (ConfigInterface $field) {
                    return Inflector::camelize($field->getId()->getFieldName());
                },
                $fields
            )
        );

        return ['entity' => $fields];
    }
}
