<?php

declare(strict_types=1);

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Creates the default data transformer for the entity select or create inline form type.
 * The created transformers are restricted to the entities the current user is allowed to view,
 * unless the ACL protection is explicitly disabled for a form type that validates the submitted value on its own.
 */
class EntitySelectOrCreateDataTransformerFactory
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly AclHelper $aclHelper
    ) {
    }

    /**
     * Creates a transformer that resolves an entity by its identifier and, when creating a new entity is granted,
     * is able to create a new entity from the submitted value.
     */
    public function createTransformer(
        string $entityClass,
        ?string $newItemPropertyName = null,
        bool $newItemAllowEmptyProperty = false,
        ?string $newItemValuePath = null,
        bool $isCreateGranted = true,
        bool $aclProtected = true
    ): DataTransformerInterface {
        $aclHelper = $aclProtected ? $this->aclHelper : null;

        if ($newItemPropertyName && $isCreateGranted) {
            $transformer = new EntityCreationTransformer($this->doctrine, $entityClass);
            $transformer->setNewEntityPropertyName($newItemPropertyName);
            $transformer->setAllowEmptyProperty($newItemAllowEmptyProperty);
            $transformer->setValuePath($newItemValuePath);
            $transformer->setAclHelper($aclHelper);

            return $transformer;
        }

        $transformer = new EntityToIdTransformer($this->doctrine, $entityClass);
        $transformer->setAclHelper($aclHelper);

        return $transformer;
    }
}
