<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\FormBundle\Form\Exception\FormException;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Transforms between an entity and its ID.
 */
class EntityToIdTransformer implements DataTransformerInterface
{
    protected ManagerRegistry $doctrine;
    protected string $className;
    private ?string $property;
    /** @var callable */
    protected $queryBuilderCallback;
    private ?PropertyAccessorInterface $propertyAccessor = null;
    private ?PropertyPath $propertyPath = null;

    public function __construct(
        ManagerRegistry $doctrine,
        string $className,
        ?string $property = null,
        mixed $queryBuilderCallback = null
    ) {
        $this->doctrine = $doctrine;
        $this->className = $className;
        $this->property = $property;
        if (null !== $queryBuilderCallback && !\is_callable($queryBuilderCallback)) {
            throw new UnexpectedTypeException($queryBuilderCallback, 'callable');
        }
        $this->queryBuilderCallback = $queryBuilderCallback;
    }

    #[\Override]
    public function transform($value)
    {
        if (null === $value) {
            return null;
        }

        if (!\is_object($value)) {
            throw new UnexpectedTypeException($value, 'object');
        }

        return $this->getPropertyAccessor()->getValue($value, $this->getPropertyPath());
    }

    #[\Override]
    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        }

        return $this->loadEntityById($value);
    }

    /**
     * @throws UnexpectedTypeException if query builder callback returns invalid type
     * @throws TransformationFailedException if value not matched given $id
     */
    protected function loadEntityById(mixed $id): object
    {
        /** @var EntityRepository $repository */
        $repository = $this->doctrine->getRepository($this->className);
        if ($this->queryBuilderCallback) {
            /** @var QueryBuilder $qb */
            $qb = \call_user_func($this->queryBuilderCallback, $repository, $id);
            if (!$qb instanceof QueryBuilder) {
                throw new UnexpectedTypeException($qb, QueryBuilder::class);
            }
            $result = $qb->getQuery()->execute();
        } else {
            $result = $repository->find($id);
            if ($result) {
                $result = [$result];
            }
        }

        if (null === $result || \count($result) !== 1) {
            throw new TransformationFailedException(\sprintf('The value "%s" does not exist or not unique.', $id));
        }

        return reset($result);
    }

    protected function getProperty(): string
    {
        if (null === $this->property) {
            $meta = $this->doctrine->getManagerForClass($this->className)->getClassMetadata($this->className);
            try {
                $this->property = $meta->getSingleIdentifierFieldName();
            } catch (MappingException $e) {
                throw new FormException(\sprintf(
                    'Cannot get id property path of entity. "%s" has composite primary key.',
                    $this->className
                ));
            }
        }

        return $this->property;
    }

    protected function getPropertyPath(): PropertyPathInterface
    {
        if (null === $this->propertyPath) {
            $this->propertyPath = new PropertyPath($this->getProperty());
        }

        return $this->propertyPath;
    }

    protected function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = $this->createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    protected function createPropertyAccessor(): PropertyAccessorInterface
    {
        return PropertyAccess::createPropertyAccessorWithDotSyntax();
    }
}
