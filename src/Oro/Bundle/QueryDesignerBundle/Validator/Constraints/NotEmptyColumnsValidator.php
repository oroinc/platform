<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates whether a query definition created by the query designer has at least one column.
 */
class NotEmptyColumnsValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotEmptyColumns) {
            throw new UnexpectedTypeException($constraint, NotEmptyColumns::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof AbstractQueryDesigner) {
            throw new UnexpectedTypeException($value, AbstractQueryDesigner::class);
        }

        try {
            $definition = QueryDefinitionUtil::decodeDefinition($value->getDefinition());
        } catch (InvalidConfigurationException $e) {
            return;
        }

        if (empty($definition['columns'])) {
            $this->context->addViolation($constraint->message);
        }
    }
}
