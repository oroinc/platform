<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validator for ensuring field names are not SQL reserved keywords.
 *
 * This validator checks that custom field names do not conflict with SQL reserved words
 * for the configured database platform. Using reserved keywords as column names would cause
 * SQL syntax errors when the database schema is created or modified.
 */
class NotSqlKeywordValidator extends ConstraintValidator
{
    const ALIAS = 'oro_entity_extend.validator.not_sql_keyword';

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $value = (string)$value;

        if ($this->isReservedWord($value)) {
            $this->context->addViolation($constraint->message, array('{{ value }}' => $value));
        }
    }

    /**
     * Determines whether the given value is reserved SQL word or not
     *
     * @param string $value
     * @return bool
     */
    protected function isReservedWord($value)
    {
        return $this->doctrine->getConnection()->getDatabasePlatform()->getReservedKeywordsList()->isKeyword($value);
    }
}
