<?php

namespace Oro\Bundle\ActionBundle\Layout\DataProvider;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ActionButtonsProvider
{
    /** @var ApplicationsHelper */
    protected $applicationsHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param ApplicationsHelper $applicationsHelper
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ApplicationsHelper $applicationsHelper, DoctrineHelper $doctrineHelper)
    {
        $this->applicationsHelper = $applicationsHelper;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @return string
     */
    public function getDialogRoute()
    {
        return $this->applicationsHelper->getDialogRoute();
    }

    /**
     * @return string
     */
    public function getExecutionRoute()
    {
        return $this->applicationsHelper->getExecutionRoute();
    }

    /**
     * @param object|string $entity
     *
     * @return string
     */
    public function getEntityClass($entity)
    {
        return is_object($entity) ? ClassUtils::getClass($entity) : ClassUtils::getRealClass($entity);
    }

    /**
     * @param object $entity
     *
     * @return int|null
     */
    public function getEntityId($entity)
    {
        return is_object($entity) ? $this->doctrineHelper->getSingleEntityIdentifier($entity) : null;
    }
}
