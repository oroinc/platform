<?php

namespace Oro\Bundle\UIBundle\Provider;

use Oro\Bundle\UIBundle\Twig\TabExtension;

/**
 * Loads tab widgets declared in navigation menu configs.
 */
class TabMenuWidgetProvider implements WidgetProviderInterface
{
    /** @var ObjectIdAccessorInterface */
    protected $objectIdAccessor;

    /** @var TabExtension */
    protected $widgetProvider;

    /** @var string */
    protected $menuName;

    /** @var string */
    protected $objectClass;

    /**
     * @param ObjectIdAccessorInterface $objectIdAccessor The object id accessor
     * @param TabExtension              $widgetProvider   The tab widgets provider
     * @param string                    $menuName         The name of the navigation menu contains
     *                                                    declarations of widgets
     * @param string|null               $objectClass      The full class name of the object
     *                                                    for which this provider is applicable
     */
    public function __construct(
        ObjectIdAccessorInterface $objectIdAccessor,
        TabExtension $widgetProvider,
        $menuName,
        $objectClass = null
    ) {
        $this->objectIdAccessor = $objectIdAccessor;
        $this->widgetProvider   = $widgetProvider;
        $this->menuName         = $menuName;
        $this->objectClass      = $objectClass;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($object)
    {
        return $this->objectClass
            ? is_a($object, $this->objectClass)
            : true;
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgets($object)
    {
        return $this->widgetProvider->getTabs(
            $this->menuName,
            ['id' => $this->objectIdAccessor->getIdentifier($object)]
        );
    }
}
