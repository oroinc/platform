<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity as DoctrineUniqueEntityConstraint;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This validator is basically a copy of @see \Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator,
 * but this validator has an additional option 'buildViolationAtEntityLevel' that allows to not build violations at
 * some property path. This had to be another class, because Doctrine's UniqueEntityValidator was written poorly,
 * without possibility to extend.
 */
class UniqueEntityValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly ContainerInterface $container,
        private readonly ConfigManager $configManager,
        private readonly TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEntity) {
            throw new UnexpectedTypeException($constraint, UniqueEntity::class);
        }

        $fields = $this->getUniqueFields($constraint);

        if (null !== $constraint->errorPath && !\is_string($constraint->errorPath)) {
            throw new UnexpectedTypeException($constraint->errorPath, 'string or null');
        }

        $em = $this->getEntityManager($value, $constraint);
        $criteria = $this->buildCriteria($value, $fields, $constraint, $em);
        $result = $this->getResult($criteria, $value, $constraint, $em);
        if ($this->isNoDuplicates($result, $value)) {
            return;
        }

        $entityClass = ClassUtils::getClass($value);
        if ($constraint->buildViolationAtEntityLevel) {
            $this->buildViolationAtEntityLevel($constraint, $entityClass, $fields);
        } else {
            $this->buildViolationAtPath($constraint, $entityClass, $fields, $criteria);
        }
    }

    /**
     * @return string[]
     */
    public function getUniqueFields(UniqueEntity $constraint): array
    {
        if (!\is_array($constraint->fields) && !\is_string($constraint->fields)) {
            throw new UnexpectedTypeException($constraint->fields, 'array');
        }

        if (\is_string($constraint->fields) && str_starts_with($constraint->fields, '%')) {
            $fields = (array)$this->container->getParameter(trim($constraint->fields, '%'));
        } else {
            $fields = (array)$constraint->fields;
        }

        if (0 === \count($fields)) {
            throw new ConstraintDefinitionException('At least one field has to be specified.');
        }

        return $fields;
    }

    private function buildViolationAtPath(
        UniqueEntity $constraint,
        string $entityClass,
        array $fields,
        array $criteria
    ): void {
        $errorPath = $constraint->errorPath ?? $fields[0];

        $this->context->buildViolation($constraint->message)
            ->atPath($errorPath)
            ->setInvalidValue($criteria[$errorPath] ?? $criteria[$fields[0]])
            ->setParameter('{{ unique_key }}', $this->formatValues($fields))
            ->setParameter('{{ unique_fields }}', $this->formatValues($this->getFieldLabels($entityClass, $fields)))
            ->setCode(DoctrineUniqueEntityConstraint::NOT_UNIQUE_ERROR)
            ->addViolation();
    }

    private function getFieldLabels(string $entityClass, array $fields): array
    {
        $fieldLabels = [];
        foreach ($fields as $fieldName) {
            $fieldLabels[] = $this->translator->trans(
                $this->configManager->getFieldConfig('entity', $entityClass, $fieldName)
                    ->get('label', false, $fieldName)
            );
        }

        return $fieldLabels;
    }

    private function buildViolationAtEntityLevel(UniqueEntity $constraint, string $entityClass, array $fields): void
    {
        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ unique_key }}', $this->formatValues($fields))
            ->setParameter('{{ unique_fields }}', $this->formatValues($this->getFieldLabels($entityClass, $fields)))
            ->setCode(DoctrineUniqueEntityConstraint::NOT_UNIQUE_ERROR)
            ->addViolation();
    }

    private function getEntityManager(object $entity, UniqueEntity $constraint): ObjectManager
    {
        if ($constraint->em) {
            $em = $this->doctrine->getManager($constraint->em);
            if (!$em) {
                throw new ConstraintDefinitionException(\sprintf(
                    'Object manager "%s" does not exist.',
                    $constraint->em
                ));
            }

            return $em;
        }

        $em = $this->doctrine->getManagerForClass(ClassUtils::getClass($entity));
        if (!$em) {
            throw new ConstraintDefinitionException(\sprintf(
                'Unable to find the object manager associated with an entity of class "%s".',
                ClassUtils::getClass($entity)
            ));
        }

        return $em;
    }

    private function buildCriteria(object $entity, array $fields, UniqueEntity $constraint, ObjectManager $em): array
    {
        $class = $em->getClassMetadata(ClassUtils::getClass($entity));

        $criteria = [];
        foreach ($fields as $fieldName) {
            if (!$class->hasField($fieldName) && !$class->hasAssociation($fieldName)) {
                throw new ConstraintDefinitionException(\sprintf(
                    'The field "%s" is not mapped by Doctrine, so it cannot be validated for uniqueness.',
                    $fieldName
                ));
            }

            $criteria[$fieldName] = $class->reflFields[$fieldName]->getValue($entity);

            if ($constraint->ignoreNull && null === $criteria[$fieldName]) {
                return [];
            }

            if (null !== $criteria[$fieldName] && $class->hasAssociation($fieldName)) {
                $em->initializeObject($criteria[$fieldName]);
            }
        }

        return $criteria;
    }

    private function getResult(array $criteria, object $entity, UniqueEntity $constraint, ObjectManager $em): mixed
    {
        $repository = $em->getRepository(ClassUtils::getClass($entity));
        $result = $repository->{$constraint->repositoryMethod}($criteria);

        if ($result instanceof \IteratorAggregate) {
            $result = $result->getIterator();
        }

        if ($result instanceof \Iterator) {
            $result->rewind();
        } elseif (\is_array($result)) {
            reset($result);
        }

        return $result;
    }

    private function isNoDuplicates(mixed $result, object $entity): bool
    {
        return
            0 === \count($result)
            || (
                1 === \count($result)
                && $entity === ($result instanceof \Iterator ? $result->current() : current($result))
            );
    }
}
