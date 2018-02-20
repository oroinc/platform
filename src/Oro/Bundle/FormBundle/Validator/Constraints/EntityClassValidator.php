<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EntityClassValidator extends ConstraintValidator
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityClassNameHelper */
    protected $entityClassNameHelper;

    /**
     * @param DoctrineHelper        $doctrineHelper
     * @param EntityClassNameHelper $entityClassNameHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityClassNameHelper $entityClassNameHelper
    ) {
        $this->doctrineHelper        = $doctrineHelper;
        $this->entityClassNameHelper = $entityClassNameHelper;
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
        try {
            $entityName = $this->entityClassNameHelper->resolveEntityClass($entityName);
        } catch (EntityAliasNotFoundException $e) {
            return false;
        }

        return $this->doctrineHelper->isManageableEntity($entityName);
    }
}
