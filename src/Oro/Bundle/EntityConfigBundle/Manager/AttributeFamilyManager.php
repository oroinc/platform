<?php

namespace Oro\Bundle\EntityConfigBundle\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;

class AttributeFamilyManager
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param int $familyId
     * @return bool
     */
    public function isAttributeFamilyDeletable($familyId)
    {
        /** @var AttributeFamilyRepository $attributeFamilyRepository */
        $attributeFamilyRepository = $this->doctrineHelper->getEntityRepository(AttributeFamily::class);
        $attributeFamily = $attributeFamilyRepository->find($familyId);

        if ($attributeFamilyRepository->countFamiliesByEntityClass($attributeFamily->getEntityClass()) === 1) {
            return false;
        }

        $entityRepository = $this->doctrineHelper->getEntityRepository($attributeFamily->getEntityClass());
        $entity = $entityRepository->findOneBy(['attributeFamily' => $attributeFamily]);
        if ($entity) {
            return false;
        }

        return true;
    }
}
