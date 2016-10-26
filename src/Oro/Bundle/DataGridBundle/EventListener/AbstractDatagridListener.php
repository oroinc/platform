<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class AbstractDatagridListener
{
    protected $doctrineHelper;

    /**
     * AbstractDatagridListener constructor.
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param DatagridConfiguration $config
     * @return array
     */
    protected function getRootEntityNameAndAlias(DatagridConfiguration $config)
    {
        $rootEntity = null;
        $rootEntityAlias = null;

        $from = $config->offsetGetByPath('[source][query][from]');
        if ($from) {
            $firstFrom = current($from);
            if (!empty($firstFrom['table']) && !empty($firstFrom['alias'])) {
                $rootEntity = $this->updateEntityClass($firstFrom['table']);
                $rootEntityAlias = $firstFrom['alias'];
            }
        }

        return [$rootEntity, $rootEntityAlias];
    }

    /**
     * @param string $entity
     * @return string
     */
    protected function updateEntityClass($entity)
    {
        return $this->doctrineHelper->getEntityManager($entity)->getClassMetadata($entity)->getName();
    }
}
