<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

use Oro\Bundle\EntityBundle\Exception\EntityExceptionInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class RelatedEntityTransformer implements DataTransformerInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param DoctrineHelper      $doctrineHelper
     * @param EntityAliasResolver $entityAliasResolver
     * @param SecurityFacade      $securityFacade
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityAliasResolver $entityAliasResolver,
        SecurityFacade $securityFacade
    ) {
        $this->doctrineHelper      = $doctrineHelper;
        $this->entityAliasResolver = $entityAliasResolver;
        $this->securityFacade      = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
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

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
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
        if (false === strpos($entityName, '\\')) {
            $entityName = $this->entityAliasResolver->getClassByAlias($entityName);
        }

        $entity = $this->doctrineHelper->getEntityRepository($entityName)->find($id);

        return $entity && $this->securityFacade->isGranted('VIEW', $entity)
            ? $entity
            : null;
    }
}
