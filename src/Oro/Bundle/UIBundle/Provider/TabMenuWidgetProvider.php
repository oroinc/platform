<?php

namespace Oro\Bundle\UIBundle\Provider;

use Oro\Bundle\UIBundle\Twig\TabExtension;

/**
 * Loads tab widgets declared in navigation menu configs.
 */
class TabMenuWidgetProvider implements WidgetProviderInterface
{
    /** @var ObjectIdentityAccessorInterface */
    protected $entityIdentifierAccessor;

    /** @var TabExtension */
    protected $widgetProvider;

    /** @var string */
    protected $menuName;

    /** @var string */
    protected $entityClass;

    /**
     * @param ObjectIdentityAccessorInterface $entityIdentifierAccessor The entity accessor
     * @param TabExtension                    $widgetProvider           The tab widgets provider
     * @param string                          $menuName                 The name of the navigation menu contains
     *                                                                  declarations of widgets
     * @param string|null                     $entityClass              The full class name of the entity
     *                                                                  for which this provider is applicable
     */
    public function __construct(
        ObjectIdentityAccessorInterface $entityIdentifierAccessor,
        TabExtension $widgetProvider,
        $menuName,
        $entityClass = null
    ) {
        $this->entityIdentifierAccessor = $entityIdentifierAccessor;
        $this->widgetProvider           = $widgetProvider;
        $this->menuName                 = $menuName;
        $this->entityClass              = $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity)
    {
        return $this->entityClass
            ? is_a($entity, $this->entityClass)
            : true;
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgets($entity)
    {
        return $this->widgetProvider->getTabs(
            $this->menuName,
            ['id' => $this->entityIdentifierAccessor->getIdentifier($entity)]
        );
    }
}
