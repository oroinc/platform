<?php

namespace Oro\Bundle\EntityBundle\Twig;

use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\EntityBundle\ORM\EntityIdAccessor;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;

class EntityExtension extends \Twig_Extension
{
    /** @var EntityIdAccessor */
    protected $entityIdAccessor;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /**
     * @param EntityIdAccessor    $entityIdAccessor
     * @param EntityRoutingHelper $entityRoutingHelper
     */
    public function __construct(
        EntityIdAccessor $entityIdAccessor,
        EntityRoutingHelper $entityRoutingHelper
    ) {
        $this->entityIdAccessor    = $entityIdAccessor;
        $this->entityRoutingHelper = $entityRoutingHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_class_name', [$this, 'getClassName']),
            new \Twig_SimpleFunction('oro_action_params', [$this, 'getActionParams'])
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
            ? $this->entityRoutingHelper->encodeClassName($className)
            : $className;
    }

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
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity';
    }
}
