<?php

namespace Oro\Bundle\EntityBundle\Twig;

use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\EntityBundle\ORM\EntityIdAccessor;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;

class EntityExtension extends \Twig_Extension
{
    /** @var EntityIdAccessor */
    protected $entityIdAccessor;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /**
     * @param EntityIdAccessor    $entityIdAccessor
     * @param EntityRoutingHelper $entityRoutingHelper
     * @param EntityNameResolver  $entityNameResolver
     * @param EntityAliasResolver $entityAliasResolver
     */
    public function __construct(
        EntityIdAccessor $entityIdAccessor,
        EntityRoutingHelper $entityRoutingHelper,
        EntityNameResolver $entityNameResolver,
        EntityAliasResolver $entityAliasResolver
    ) {
        $this->entityIdAccessor    = $entityIdAccessor;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->entityNameResolver  = $entityNameResolver;
        $this->entityAliasResolver = $entityAliasResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_class_name', [$this, 'getClassName']),
            new \Twig_SimpleFunction('oro_class_alias', [$this, 'getClassAlias']),
            new \Twig_SimpleFunction('oro_action_params', [$this, 'getActionParams'])
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

        return $escape
            ? $this->entityRoutingHelper->getUrlSafeClassName($className)
            : $className;
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
            ? $this->entityAliasResolver->getPluralAlias($className)
            : $this->entityAliasResolver->getAlias($className);
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

        return $this->entityRoutingHelper->getRouteParameters(
            $this->getClassName($object, true),
            $this->entityIdAccessor->getIdentifier($object),
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
        return $this->entityNameResolver->getName($object, null, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity';
    }
}
