<?php

namespace Oro\Bundle\EntityConfigBundle\Datagrid;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;

class AttributeFamilyActionsConfiguration
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var EntityManager */
    private $entityManager;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param EntityManager                 $entityManager
     */
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
