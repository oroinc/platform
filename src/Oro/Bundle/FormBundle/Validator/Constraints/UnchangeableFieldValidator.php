<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Doctrine\ORM\Query;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class UnchangeableFieldValidator extends ConstraintValidator
{
    const OBJECT_ALIAS = 'o';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $current = $this->findExistingObject();

        if ($current === null) {
            return;
        }

        $currentValue = $current[sprintf('%s_%s', self::OBJECT_ALIAS, $this->context->getPropertyName())];

        if ($currentValue === null) {
            return;
        }

        if ($currentValue === $value) {
            return;
        }

        $this->context->addViolation($constraint->message);
    }

    /**
     * @return array|null
     */
    private function findExistingObject()
    {
        $manager = $this->doctrineHelper->getEntityManagerForClass($this->context->getClassName());

        $identifier = $manager->getClassMetadata($this->context->getClassName())
            ->getIdentifierValues($this->context->getObject());

        if (empty($identifier)) {
            return null;
        }

        $qb = $manager->getRepository($this->context->getClassName())
            ->createQueryBuilder(self::OBJECT_ALIAS);

        foreach ($identifier as $name => $identifierValue) {
            $qb->andWhere(sprintf('%s.%s = :%s', self::OBJECT_ALIAS, $name, $name));
        }

        $qb->setParameters($identifier);

        return $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_SCALAR);
    }


}
