<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Sync\EmailSynchronizationManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class EmailGridHelper
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EmailSynchronizationManager */
    protected $emailSyncManager;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var string */
    protected $userClass;

    /** @var EmailOrigin[] */
    private $emailOrigins;

    /**
     * @param DoctrineHelper              $doctrineHelper
     * @param EmailSynchronizationManager $emailSyncManager
     * @param ActivityManager             $activityManager
     * @param string                      $userClass
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EmailSynchronizationManager $emailSyncManager,
        ActivityManager $activityManager,
        $userClass
    ) {
        $this->doctrineHelper   = $doctrineHelper;
        $this->emailSyncManager = $emailSyncManager;
        $this->activityManager  = $activityManager;
        $this->userClass        = $userClass;
    }

    /**
     * @param string $entityClass
     * @return bool
     */
    public function isUserEntity($entityClass)
    {
        return $entityClass === $this->userClass;
    }

    /**
     * @param ParameterBag $parameters
     * @param mixed        $userId
     */
    public function handleRefresh($parameters, $userId)
    {
        $additionalParameters = $parameters->get(ParameterBag::ADDITIONAL_PARAMETERS);
        if (!empty($additionalParameters) && array_key_exists('refresh', $additionalParameters)) {
            $emailOrigins = $this->getEmailOrigins($userId);
            if (!empty($emailOrigins)) {
                $this->emailSyncManager->syncOrigins($emailOrigins);
            }
        }
    }

    /**
     * @param OrmDatasource $datasource
     * @param mixed         $entityId
     * @param string        $entityClass
     */
    public function updateDatasource($datasource, $entityId, $entityClass = null)
    {
        return; // fixme CRM-2480
        // apply activity filter
        $this->activityManager->addFilterByTargetEntity(
            $datasource->getQueryBuilder(),
            $entityClass ? $entityClass : $this->userClass,
            $entityId ? $entityId : -1
        );
    }

    /**
     * @param mixed $userId
     * @return EmailOrigin[]
     */
    public function getEmailOrigins($userId)
    {
        if (null === $this->emailOrigins) {
            if ($userId) {
                $user = $this->doctrineHelper
                    ->getEntityManager($this->userClass)
                    ->getRepository($this->userClass)
                    ->find($userId);

                $this->emailOrigins = $user->getEmailOrigins();
            } else {
                $this->emailOrigins = [];
            }
        }

        return $this->emailOrigins;
    }
}
