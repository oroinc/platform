<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class UnchangeableFieldValidator extends ConstraintValidator
{
    const OBJECT_ALIAS = 'o';

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
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $associations = $this->getManager()->getClassMetadata($this->context->getClassName())->getAssociationMappings();

        $current = $this->findExistingObject($associations);

        if ($current === null) {
            return;
        }

        if (is_object($value) && array_key_exists($this->context->getPropertyName(), $associations)) {
            $value = $this->getIdentifierFromObject($value, $associations);
            $current = (string)$current;
        }

        if ($current === $value) {
            return;
        }

        $this->context->addViolation($constraint->message);
    }

    /**
     * @return array|null
     */
    private function findExistingObject(array $associations)
    {
        $identifier = $this->getIdentifier($this->context->getClassName(), $this->context->getObject());

        if (empty($identifier)) {
            return null;
        }

        $qb = $this->getManager()->getRepository($this->context->getClassName())
            ->createQueryBuilder(self::OBJECT_ALIAS);

        if (array_key_exists($this->context->getPropertyName(), $associations)) {
            $qb->select(sprintf('IDENTITY(%s.%s)', self::OBJECT_ALIAS, $this->context->getPropertyName()));
        } else {
            $qb->select(sprintf('%s.%s', self::OBJECT_ALIAS, $this->context->getPropertyName()));
        }


        foreach ($identifier as $name => $identifierValue) {
            $qb->andWhere(sprintf('%s.%s = :%s', self::OBJECT_ALIAS, $name, $name));
        }

        $qb->setParameters($identifier);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param string $className
     * @param object $object
     * @return array
     */
    private function getIdentifier($className, $object)
    {
        $manager = $this->doctrineHelper->getEntityManagerForClass($className);

        return $manager->getClassMetadata($className)
            ->getIdentifierValues($object);
    }

    /**
     * @param object $value
     * @param array $associations
     * @return string
     */
    private function getIdentifierFromObject($value, $associations): string
    {
        $identifier = $this->getIdentifier(
            $associations[$this->context->getPropertyName()]['targetEntity'],
            $value
        );

        if (count($identifier) > 1) {
            throw new \LogicException(
                sprintf(
                    '%s is not allowed to be used in relational fields, where relation has identifier '
                    . 'created from more than one property.',
                    self::class
                )
            );
        }

        return (string)array_shift($identifier);
    }

    /**
     * @return \Doctrine\ORM\EntityManager|null
     */
    private function getManager()
    {
        return $this->doctrineHelper->getEntityManagerForClass($this->context->getClassName());
    }
}
