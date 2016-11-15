<?php

namespace Oro\Bundle\ActionBundle\Layout\DataProvider;

use Oro\Bundle\ActionBundle\Model\ButtonInterface;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
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
     * @param object $entity
     *
     * @return ButtonInterface[]
     */
    public function getAll($entity = null)
    {
        return $this->getByGroup($entity);
    }

    /**
     * @param object|null $entity
     * @param string|null $group
     *
     * @return ButtonInterface[]
     */
    public function getByGroup($entity = null, $group = null)
    {
        $buttons = $this->buttonProvider->findAll(
            $this->prepareButtonSearchContext($entity, $group)
        );

        return $buttons;
    }

    /**
     * @param object $entity
     * @param string|null $group
     *
     * @return ButtonSearchContext
     */
    private function prepareButtonSearchContext($entity = null, $group = null)
    {
        $buttonSearchContext = $this->contextProvider->getButtonSearchContext();
        $buttonSearchContext->setGridName(null);

        if (is_object($entity) &&
            !$this->doctrineHelper->isNewEntity($entity)
        ) {
            $entityClass = $this->doctrineHelper->getEntityClass($entity);
            $entityId = $this->doctrineHelper->getEntityIdentifier($entity);
            $buttonSearchContext->setEntity($entityClass, $entityId);
        }

        if (null !== $group) {
            $buttonSearchContext->setGroup($group);
        }

        return $buttonSearchContext;
    }
}
