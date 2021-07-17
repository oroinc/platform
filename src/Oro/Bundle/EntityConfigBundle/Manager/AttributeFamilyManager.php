<?php

namespace Oro\Bundle\EntityConfigBundle\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Provides functionality to work with attribute families.
 */
class AttributeFamilyManager
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var AclHelper */
    private $aclHelper;

    /** @var array */
    private $attributeFamilies = [];

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function setAclHelper(AclHelper $aclHelper): void
    {
        $this->aclHelper = $aclHelper;
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

    public function getAttributeFamilyByCode(string $code): ?AttributeFamily
    {
        if (!array_key_exists($code, $this->attributeFamilies)) {
            $repository = $this->doctrineHelper->getEntityRepository(AttributeFamily::class);

            $this->attributeFamilies[$code] = $repository->getFamilyByCode($code, $this->aclHelper);
        }

        return $this->attributeFamilies[$code];
    }
}
