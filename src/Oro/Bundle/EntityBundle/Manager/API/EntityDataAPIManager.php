<?php

namespace Oro\Bundle\EntityBundle\Manager\Api;

use FOS\RestBundle\Util\Codes;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

use Oro\Bundle\EntityBundle\Entity\Manager\Field\EntityFieldManager;

class EntityDataAPIManager
{
    /** @var  EntityFieldManager */
    protected $entityDataManager;

    /** @var  AuthorizationChecker */
    protected $securityService;

    public function __construct(
        EntityFieldManager $entityDataManager,
        AuthorizationChecker $securityService
    ) {
        $this->entityDataManager = $entityDataManager;
        $this->securityService = $securityService;
    }

    public function patch($entity, $data)
    {
        if (!$this->securityService->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException();
        }

        $result = $this->entityDataManager->update($entity, $data);
        $form = $result['form'];
        $changeSet = $result['changeSet'];

        if ($form->getErrors()->count() > 0) {
            throw new \Exception($form->getErrors()->first()->getMessage(), Codes::HTTP_BAD_REQUEST);
        }

        return $changeSet;
    }
}
