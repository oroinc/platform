<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class NormalizeFilters implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $filters = $context->getFilters();
        if (empty($filters)) {
            // nothing to normalize
            return;
        }

        $fields = !empty($filters['fields'])
            ? $filters['fields']
            : [];

        if (isset($filters['exclusion_policy']) && $filters['exclusion_policy'] === 'all') {
            $fields = $this->removeExclusions($fields);
        } else {
            $entityClass = $context->getClassName();
            if ($entityClass && $this->doctrineHelper->isManageableEntity($entityClass)) {
                $fields = $this->removeExclusions(
                    $this->completeFields($fields, $entityClass)
                );
            }
        }

        $context->setFilters(
            [
                'exclusion_policy' => 'all',
                'fields'           => $fields
            ]
        );
    }

    /**
     * @param array  $fields
     * @param string $entityClass
     *
     * @return array
     */
    protected function completeFields(array $fields, $entityClass)
    {
        $metadata = $this->doctrineHelper->getEntityMetadata($entityClass);

        $fieldNames = $metadata->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            if (!isset($fields[$fieldName])) {
                $fields[$fieldName] = [
                    'data_type' => $metadata->getTypeOfField($fieldName)
                ];
            }
        }

        return $fields;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    protected function removeExclusions(array $fields)
    {
        return array_filter(
            $fields,
            function (array $fieldConfig) {
                return !isset($fieldConfig['exclude']) || !$fieldConfig['exclude'];
            }
        );
    }
}
