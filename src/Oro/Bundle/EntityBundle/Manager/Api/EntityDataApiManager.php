<?php

namespace Oro\Bundle\EntityBundle\Manager\Api;

use Oro\Bundle\EntityBundle\Entity\Manager\Field\EntityFieldManager;
use Oro\Bundle\EntityBundle\Exception\FieldUpdateAccessException;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class EntityDataApiManager
{
    /** @var  EntityFieldManager */
    protected $entityDataManager;

    /** @var  AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /**
     * @param EntityFieldManager            $entityDataManager
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param EntityRoutingHelper           $entityRoutingHelper
     */
    public function __construct(
        EntityFieldManager $entityDataManager,
        AuthorizationCheckerInterface $authorizationChecker,
        EntityRoutingHelper $entityRoutingHelper
    ) {
        $this->entityDataManager = $entityDataManager;
        $this->authorizationChecker = $authorizationChecker;
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

        if (!$this->authorizationChecker->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException();
        }

        try {
            return $this->entityDataManager->update($entity, $data);
        } catch (FieldUpdateAccessException $e) {
            throw new AccessDeniedException($e->getMessage(), $e);
        }
    }
}
