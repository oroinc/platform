<?php

namespace Oro\Bundle\EntityBundle\Manager\Api;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityBundle\Entity\Manager\Field\EntityFieldManager;
use Oro\Bundle\EntityBundle\Exception\FieldUpdateAccessException;

class EntityDataApiManager
{
    /** @var  EntityFieldManager */
    protected $entityDataManager;

    /** @var  AuthorizationChecker */
    protected $securityService;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /**
     * @param EntityFieldManager   $entityDataManager
     * @param AuthorizationChecker $securityService
     * @param EntityRoutingHelper  $entityRoutingHelper
     */
    public function __construct(
        EntityFieldManager $entityDataManager,
        AuthorizationChecker $securityService,
        EntityRoutingHelper $entityRoutingHelper
    ) {
        $this->entityDataManager = $entityDataManager;
        $this->securityService = $securityService;
        $this->entityRoutingHelper = $entityRoutingHelper;
    }

    /**
     * @param string $className
     * @param int    $id
     * @param array  $data
     *
     * @return array
     *
     * @throws AccessDeniedException
     */
    public function patch($className, $id, $data)
    {
        $entity = $this->entityRoutingHelper->getEntity($className, $id);

        if (!$this->securityService->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException();
        }

        try {
            return $this->entityDataManager->update($entity, $data);
        } catch (FieldUpdateAccessException $e) {
            throw new AccessDeniedException($e->getMessage(), $e);
        }
    }
}
