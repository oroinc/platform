<?php

namespace Oro\Bundle\EntityBundle\Tools;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * The helper class intended to use in controllers that works with entities
 * which type cannot be declared statically
 */
class EntityRoutingHelper
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var UrlGeneratorInterface */
    protected $urlGenerator;

    /**
     * @param DoctrineHelper        $doctrineHelper
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(DoctrineHelper $doctrineHelper, UrlGeneratorInterface $urlGenerator)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->urlGenerator   = $urlGenerator;
    }

    /**
     * Encodes the class name into the format that can be used in route parameters
     *
     * @param string $className The class name
     *
     * @return string The encoded class name
     */
    public function encodeClassName($className)
    {
        return str_replace('\\', '_', $className);
    }

    /**
     * Decodes the given string into the class name
     *
     * @param string $className The encoded class name
     *
     * @return string The class name
     */
    public function decodeClassName($className)
    {
        return str_replace('_', '\\', $className);
    }

    /**
     * Generates a URL for a specific route based on the given parameters
     *
     * @param string $routeName
     * @param string $entityClass
     * @param mixed  $entityId
     * @param array  $additionalParameters
     * @return string
     */
    public function generateUrl($routeName, $entityClass, $entityId, $additionalParameters = [])
    {
        $parameters = $this->getRouteParameters($entityClass, $entityId);
        if (!empty($additionalParameters)) {
            $parameters = array_merge($parameters, $additionalParameters);
        }

        return $this->urlGenerator->generate($routeName, $parameters);
    }

    /**
     * Returns an array that can be used as the route parameters and which contains the entity class name and id
     *
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return array
     */
    public function getRouteParameters($entityClass, $entityId)
    {
        return [
            'entityClass' => $this->encodeClassName($entityClass),
            'entityId'    => (string)$entityId
        ];
    }

    /**
     * Returns the class name of the given entity object
     *
     * @param object $entity
     *
     * @return string
     */
    public function getEntityClass($entity)
    {
        return $this->doctrineHelper->getEntityClass($entity);
    }

    /**
     * Gets the id of the given entity object
     *
     * @param object $entity
     *
     * @return mixed
     */
    public function getSingleEntityIdentifier($entity)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    /**
     * Returns the entity object by its class name and id
     *
     * @param string $entityClass The class name. Also the _ char can be used instead of \
     * @param mixed  $entityId    The object id
     *
     * @return object The entity object
     *
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function getEntity($entityClass, $entityId)
    {
        $entityClass = $this->decodeClassName($entityClass);

        $entity = null;
        try {
            $entity = $this->doctrineHelper->getEntity($entityClass, $entityId);
        } catch (NotManageableEntityException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
        if (!$entity) {
            throw new NotFoundHttpException('Not Found');
        }

        return $entity;
    }

    /**
     * Returns the reference to the entity object by its class name and id
     *
     * If entity id is not specified the reference to the new entity object is returned
     *
     * @param string     $entityClass The class name. Also the _ char can be used instead of \
     * @param mixed|null $entityId    The object id
     *
     * @return object The entity reference
     *
     * @throws BadRequestHttpException
     */
    public function getEntityReference($entityClass, $entityId = null)
    {
        $entityClass = $this->decodeClassName($entityClass);

        try {
            $entity = $entityId
                ? $this->doctrineHelper->getEntityReference($entityClass, $entityId)
                : $this->doctrineHelper->createEntityInstance($entityClass);
        } catch (NotManageableEntityException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        return $entity;
    }
}
