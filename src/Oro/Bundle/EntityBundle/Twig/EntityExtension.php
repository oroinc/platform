<?php

namespace Oro\Bundle\EntityBundle\Twig;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\ORM\EntityIdAccessor;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

class EntityExtension extends \Twig_Extension
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return EntityIdAccessor
     */
    protected function getEntityIdAccessor()
    {
        return $this->container->get('oro_entity.entity_identifier_accessor');
    }

    /**
     * @return EntityRoutingHelper
     */
    protected function getEntityRoutingHelper()
    {
        return $this->container->get('oro_entity.routing_helper');
    }

    /**
     * @return EntityNameResolver
     */
    protected function getEntityNameResolver()
    {
        return $this->container->get('oro_entity.entity_name_resolver');
    }

    /**
     * @return EntityAliasResolver
     */
    protected function getEntityAliasResolver()
    {
        return $this->container->get('oro_entity.entity_alias_resolver');
    }

    /**
     * @return EntityFallbackResolver
     */
    protected function getEntityFallbackResolver()
    {
        return $this->container->get('oro_entity.fallback.resolver.entity_fallback_resolver');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_class_name', [$this, 'getClassName']),
            new \Twig_SimpleFunction('oro_url_class_name', [$this, 'getUrlClassName']),
            new \Twig_SimpleFunction('oro_class_alias', [$this, 'getClassAlias']),
            new \Twig_SimpleFunction('oro_action_params', [$this, 'getActionParams']),
            new \Twig_SimpleFunction('oro_entity_fallback_value', [$this, 'getFallbackValue']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('oro_format_name', [$this, 'getEntityName'], ['is_safe' => ['html']])
        ];
    }

    /**
     * Get FQCN of specified entity
     *
     * @param object $object
     * @param bool   $escape Set TRUE to escape the class name for insertion into a route,
     *                       replacing \ with _ characters
     *
     * @return string
     */
    public function getClassName($object, $escape = false)
    {
        if (!is_object($object)) {
            return null;
        }

        $className = ClassUtils::getRealClass($object);
        if (!$escape) {
            return $className;
        }

        return $this->getEntityRoutingHelper()->getUrlSafeClassName($className);
    }

    /**
     * Get URL safe class name based on passes class name
     *
     * @param string $className
     * @return string
     */
    public function getUrlClassName($className)
    {
        return $this->getEntityRoutingHelper()->getUrlSafeClassName($className);
    }

    /**
     * Get class alias of specified entity
     *
     * @param object $object
     * @param bool   $isPlural
     *
     * @return null|string
     */
    public function getClassAlias($object, $isPlural = false)
    {
        if (!is_object($object)) {
            return null;
        }

        $className = ClassUtils::getRealClass($object);

        return $isPlural
            ? $this->getEntityAliasResolver()->getPluralAlias($className)
            : $this->getEntityAliasResolver()->getAlias($className);
    }

    /**
     * @param object      $object
     * @param string|null $action
     *
     * @return array
     */
    public function getActionParams($object, $action = null)
    {
        if (!is_object($object)) {
            return [];
        }

        return $this->getEntityRoutingHelper()->getRouteParameters(
            $this->getClassName($object, true),
            $this->getEntityIdAccessor()->getIdentifier($object),
            $action
        );
    }

    /**
     * Returns a text representation of the given entity.
     *
     * @param object $object
     * @param string $locale
     *
     * @return string
     */
    public function getEntityName($object, $locale = null)
    {
        return $this->getEntityNameResolver()->getName($object, null, $locale);
    }

    /**
     * @param object $object
     * @param string $objectFieldName
     * @param int    $level
     *
     * @return mixed
     */
    public function getFallbackValue($object, $objectFieldName, $level = 1)
    {
        return $this->getEntityFallbackResolver()->getFallbackValue($object, $objectFieldName, $level);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity';
    }
}
