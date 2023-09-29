<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\FieldAcl\Configuration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityExtendBundle\Grid\FieldsHelper;
use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Removes fields without permissions from a datagrid.
 */
class DataGridFieldAclListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private FieldAclHelper $fieldAclHelper,
        private FieldsHelper $fieldsHelper
    ) {
    }
    public function onPreBuild(PreBuild $event): void
    {
        $config = $event->getConfig();
        $className = $config->getExtendedEntityClassName();
        if (!$className) {
            return;
        }

        if (!$this->isSupported($className)) {
            return;
        }

        # Return all additional fields for entities based on the organization.
        $fields = $this->fieldsHelper->getFields($config->getExtendedEntityClassName());
        foreach ($fields as $field) {
            $fieldName = $field->getFieldName();
            $config->offsetAddToArrayByPath(Configuration::COLUMNS_PATH, [$fieldName => ['data_name' => $fieldName]]);
        }
    }

    public function onBuildAfter(BuildAfter $event): void
    {
        $config = $event->getDatagrid()->getConfig();
        $className = $config->getExtendedEntityClassName();
        if (!$className) {
            return;
        }

        if (!$this->isSupported($className)) {
            return;
        }

        $columns = $config->offsetGetByPath(Configuration::COLUMNS_PATH, []);
        foreach ($columns as $name => $data) {
            if (isset($data[PropertyInterface::DISABLED_KEY]) && $data[PropertyInterface::DISABLED_KEY]) {
                continue;
            }

            $source = $data['source_name'] ?? $name;
            $column = $data['column_name'] ?? $name;
            if (!$this->fieldAclHelper->isFieldViewGranted(new ObjectIdentity('entity', $className), $source)) {
                if ($config->offsetExistByPath(sprintf('[columns][%s]', $column))) {
                    $name = $column;
                }
                $config->removeColumn($name);
            }
        }
    }

    private function isSupported(string $className): bool
    {
        if (!$this->fieldAclHelper->isFieldAclEnabled($className)) {
            return false;
        }

        if (!$this->tokenStorage->getToken()) {
            return false;
        }

        return true;
    }
}
