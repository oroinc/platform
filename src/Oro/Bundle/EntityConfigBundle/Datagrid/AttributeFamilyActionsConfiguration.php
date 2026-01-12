<?php

namespace Oro\Bundle\EntityConfigBundle\Datagrid;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Configures visibility of datagrid actions for attribute families based on user permissions.
 *
 * This class determines which actions (view, edit, delete) should be visible for each attribute family
 * record in a datagrid, using authorization checks to ensure users can only perform actions they are
 * permitted to execute.
 */
class AttributeFamilyActionsConfiguration
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var EntityManager */
    private $entityManager;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, EntityManager $entityManager)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->entityManager = $entityManager;
    }

    /**
     * @param ResultRecordInterface $record
     * @return array
     */
    public function configureActionsVisibility(ResultRecordInterface $record)
    {
        $attributeFamily = $this->entityManager->getReference(AttributeFamily::class, $record->getValue('id'));

        return [
            'view' => true,
            'edit' => true,
            'delete' => $this->authorizationChecker->isGranted('delete', $attributeFamily)
        ];
    }
}
