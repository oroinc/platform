<?php

namespace Oro\Bundle\EntityConfigBundle\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

class AttributeFamilyVoter extends AbstractEntityVoter
{
    const ATTRIBUTE_DELETE = 'delete';

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        parent::__construct($doctrineHelper);
        $this->supportedAttributes = [self::ATTRIBUTE_DELETE];
        $this->className = AttributeFamily::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        /** @var AttributeFamilyRepository $attributeFamilyRepository */
        $attributeFamilyRepository = $this->doctrineHelper->getEntityRepository($this->className);
        $attributeFamily = $attributeFamilyRepository->find($identifier);

        if ($attributeFamilyRepository->countFamiliesByEntityClass($attributeFamily->getEntityClass()) === 1) {
            return self::ACCESS_DENIED;
        }

        $entityRepository = $this->doctrineHelper->getEntityRepository($attributeFamily->getEntityClass());
        $entity = $entityRepository->findOneBy(['attributeFamily' => $attributeFamily]);
        if ($entity) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }
}
