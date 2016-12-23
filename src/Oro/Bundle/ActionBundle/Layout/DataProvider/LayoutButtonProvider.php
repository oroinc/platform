<?php

namespace Oro\Bundle\ActionBundle\Layout\DataProvider;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Provider\ButtonProvider;
use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class LayoutButtonProvider
{
    /** @var ButtonProvider */
    protected $buttonProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ButtonSearchContextProvider */
    protected $contextProvider;

    /**
     * @param ButtonProvider $buttonProvider
     * @param DoctrineHelper $doctrineHelper
     * @param ButtonSearchContextProvider $contextProvider
     */
    public function __construct(
        ButtonProvider $buttonProvider,
        DoctrineHelper $doctrineHelper,
        ButtonSearchContextProvider $contextProvider
    ) {
        $this->buttonProvider = $buttonProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->contextProvider = $contextProvider;
    }

    /**
     * @param object|null $entity
     * @param string|null $datagrid
     *
     * @return ButtonInterface[]
     */
    public function getAll($entity = null, $datagrid = null)
    {
        return $this->getByGroup($entity, $datagrid);
    }

    /**
     * @param object|null $entity
     * @param string|null $datagrid
     * @param string|null $group
     *
     * @return ButtonInterface[]
     */
    public function getByGroup($entity = null, $datagrid = null, $group = null)
    {
        return $this->buttonProvider->findAvailable(
            $this->prepareButtonSearchContext($entity, $datagrid, $group)
        );
    }

    /**
     * @param object $entity
     * @param string|null $datagrid
     * @param string|null $group
     *
     * @return ButtonSearchContext
     */
    private function prepareButtonSearchContext($entity = null, $datagrid = null, $group = null)
    {
        $buttonSearchContext = $this->contextProvider->getButtonSearchContext()
            ->setGroup($group)
            ->setDatagrid($datagrid);

        if (is_object($entity)) {
            $entityClass = $this->doctrineHelper->getEntityClass($entity);
            $entityId = null;
            if (!$this->doctrineHelper->isNewEntity($entity)) {
                $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
            }

            $buttonSearchContext->setEntity($entityClass, $entityId);
        }

        return $buttonSearchContext;
    }
}
