<?php

namespace Oro\Bundle\ActionBundle\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\Criteria\OperationFindCriteria;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DeleteMassActionExtension as BaseDeleteMassActionExtension;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class DeleteMassActionExtension extends BaseDeleteMassActionExtension
{
    const OPERATION_NAME = 'DELETE';

    /** @var OperationRegistry */
    protected $operationRegistry;

    /** @var ContextHelper */
    private $contextHelper;

    /** @var array */
    protected $groups;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EntityClassResolver $entityClassResolver
     * @param OperationRegistry $operationRegistry
     * @param ContextHelper $contextHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityClassResolver $entityClassResolver,
        OperationRegistry $operationRegistry,
        ContextHelper $contextHelper
    ) {
        parent::__construct($doctrineHelper, $entityClassResolver);

        $this->operationRegistry = $operationRegistry;
        $this->contextHelper = $contextHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function isDeleteActionExists(DatagridConfiguration $config, $key)
    {
        if ($key !== static::ACTION_KEY) {
            return parent::isDeleteActionExists($config, $key);
        }

        $operation = $this->operationRegistry->findByName(
            self::OPERATION_NAME,
            new OperationFindCriteria($this->getEntity($config), null, $config->getName(), $this->groups)
        );

        if (!$operation instanceof Operation) {
            return false;
        }

        return $operation->isAvailable(
            $this->contextHelper->getActionData(
                $this->getDatagridContext($config)
            )
        );
    }

    /**
     * @param array $groups
     */
    public function setGroups(array $groups)
    {
        $this->groups = $groups;
    }

    /**
     * @param DatagridConfiguration $config
     * @return array
     */
    protected function getDatagridContext(DatagridConfiguration $config)
    {
        return [
            ContextHelper::ENTITY_CLASS_PARAM => $this->getEntity($config),
            ContextHelper::DATAGRID_PARAM => $config->getName(),
            ContextHelper::GROUP_PARAM => $this->groups,
        ];
    }
}
