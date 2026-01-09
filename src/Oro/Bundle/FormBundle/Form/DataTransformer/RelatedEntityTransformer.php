<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Exception\EntityExceptionInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Transforms related entities to/from their identifier and class representation with authorization checks.
 */
class RelatedEntityTransformer implements DataTransformerInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityClassNameHelper */
    protected $entityClassNameHelper;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityClassNameHelper $entityClassNameHelper,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityClassNameHelper = $entityClassNameHelper;
        $this->authorizationChecker = $authorizationChecker;
    }

    #[\Override]
    public function transform($value): mixed
    {
        if (null === $value) {
            return null;
        }

        if (!is_object($value)) {
            throw new UnexpectedTypeException($value, 'object');
        }

        return [
            'id'     => $this->doctrineHelper->getSingleEntityIdentifier($value),
            'entity' => ClassUtils::getClass($value)
        ];
    }

    #[\Override]
    public function reverseTransform($value): mixed
    {
        if (!$value) {
            return null;
        }

        if (!is_array($value) || empty($value['entity']) || empty($value['id'])) {
            return $value;
        }

        try {
            return $this->getEntity($value['entity'], $value['id']) ?: $value;
        } catch (EntityExceptionInterface $e) {
            return $value;
        }
    }

    /**
     * Finds an entity of the given type by its id
     *
     * @param string $entityName The class name of alias of the entity
     * @param mixed  $id         The id of the entity
     *
     * @return object|null
     */
    protected function getEntity($entityName, $id)
    {
        $entityName = $this->entityClassNameHelper->resolveEntityClass($entityName);
        $entity     = $this->doctrineHelper->getEntityRepository($entityName)->find($id);

        return $entity && $this->authorizationChecker->isGranted('VIEW', $entity)
            ? $entity
            : null;
    }
}
