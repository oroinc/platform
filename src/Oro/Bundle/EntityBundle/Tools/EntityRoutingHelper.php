<?php

namespace Oro\Bundle\EntityBundle\Tools;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\Exception\RecordNotFoundException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The helper class intended to use in controllers that works with entities
 * which type cannot be declared statically
 */
class EntityRoutingHelper
{
    const PARAM_ACTION = '_action';
    const PARAM_ENTITY_CLASS = 'entityClass';
    const PARAM_ENTITY_ID = 'entityId';

    /** @var EntityClassNameHelper */
    protected $entityClassNameHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var UrlGeneratorInterface */
    protected $urlGenerator;

    /**
     * @param EntityClassNameHelper $entityClassNameHelper
     * @param DoctrineHelper        $doctrineHelper
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(
        EntityClassNameHelper $entityClassNameHelper,
        DoctrineHelper $doctrineHelper,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->entityClassNameHelper = $entityClassNameHelper;
        $this->doctrineHelper        = $doctrineHelper;
        $this->urlGenerator          = $urlGenerator;
    }

    /**
     * Decodes the given string into the class name
     *
     * @param string $className The encoded class name
     *
     * @return string The class name
     *
     * @deprecated since 1.8, use resolveEntityClass
     */
    public function decodeClassName($className)
    {
        return $this->entityClassNameHelper->resolveEntityClass($className, true);
    }

    /**
     * Converts the class name to a form that can be safely used in URL
     *
     * @param string $className The class name
     *
     * @return string The URL-safe representation of a class name
     */
    public function getUrlSafeClassName($className)
    {
        return $this->entityClassNameHelper->getUrlSafeClassName($className);
    }

    /**
     * Resolves the entity class name
     *
     * @param string $entityName    The class name, url-safe class name, alias or plural alias of the entity
     * @param bool   $isPluralAlias Determines whether the entity name may be a singular of plural alias
     *
     * @return string The FQCN of an entity
     */
    public function resolveEntityClass($entityName, $isPluralAlias = true)
    {
        return $this->entityClassNameHelper->resolveEntityClass($entityName, $isPluralAlias);
    }

    /**
     * Gets an entity action form a query string.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function getAction(Request $request)
    {
        return $request->query->get(self::PARAM_ACTION);
    }

    /**
     * Gets an entity class name form a query string.
     *
     * @param Request $request
     * @param string  $paramName
     *
     * @return string|null
     */
    public function getEntityClassName(Request $request, $paramName = self::PARAM_ENTITY_CLASS)
    {
        $className = $request->query->get($paramName);
        if ($className) {
            $className = $this->resolveEntityClass($className);
        }

        return $className;
    }

    /**
     * Gets an entity id form a query string.
     *
     * @param Request $request
     * @param string  $paramName
     *
     * @return mixed
     */
    public function getEntityId(Request $request, $paramName = self::PARAM_ENTITY_ID)
    {
        return $request->query->get($paramName);
    }

    /**
     * Generates a URL for a specific route based on the given parameters
     *
     * @param string $routeName
     * @param string $entityClass
     * @param mixed  $entityId
     * @param array  $additionalParameters
     *
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
     * Generates a URL for a specific route based on the given parameters
     *
     * @param string  $routeName
     * @param Request $request
     * @param array   $additionalParameters
     *
     * @return string
     */
    public function generateUrlByRequest($routeName, Request $request, $additionalParameters = [])
    {
        return $this->urlGenerator->generate(
            $routeName,
            array_merge($request->query->all(), $additionalParameters)
        );
    }

    /**
     * Returns an array that can be used as the route parameters for entity related actions
     *
     * @param string      $entityClass
     * @param mixed       $entityId
     * @param string|null $action
     *
     * @return array
     */
    public function getRouteParameters($entityClass, $entityId, $action = null)
    {
        $params = [
            self::PARAM_ENTITY_CLASS => $this->getUrlSafeClassName($entityClass),
            self::PARAM_ENTITY_ID    => (string)$entityId
        ];
        if ($action) {
            $params[self::PARAM_ACTION] = $action;
        }

        return $params;
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
     * @throws RecordNotFoundException in case entity not found
     */
    public function getEntity($entityClass, $entityId)
    {
        $entityClass = $this->resolveEntityClass($entityClass);

        $entity = null;
        try {
            $entity = $this->doctrineHelper->getEntity($entityClass, $entityId);
        } catch (NotManageableEntityException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
        if (!$entity) {
            throw new RecordNotFoundException(
                sprintf("Record doesn't exist")
            );
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
        $entityClass = $this->resolveEntityClass($entityClass);

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
