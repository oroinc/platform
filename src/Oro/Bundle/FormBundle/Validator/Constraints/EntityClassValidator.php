<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

class EntityClassValidator extends ConstraintValidator
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /**
     * @param DoctrineHelper      $doctrineHelper
     * @param EntityAliasResolver $entityAliasResolver
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityAliasResolver $entityAliasResolver
    ) {
        $this->doctrineHelper      = $doctrineHelper;
        $this->entityAliasResolver = $entityAliasResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value) || !$this->isValidEntityClass($value)) {
            $this->context->addViolation($constraint->message, [
                '{{ value }}' => $this->formatValue($value)
            ]);
        }
    }

    /**
     * @param string $entityName
     *
     * @return bool
     */
    protected function isValidEntityClass($entityName)
    {
        if (false === strpos($entityName, '\\')) {
            try {
                $entityName = $this->entityAliasResolver->getClassByAlias($entityName);
            } catch (EntityAliasNotFoundException $e) {
                return false;
            }
        }

        return $this->doctrineHelper->isManageableEntity($entityName);
    }
}
