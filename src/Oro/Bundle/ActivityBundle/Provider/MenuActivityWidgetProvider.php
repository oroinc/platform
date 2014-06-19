<?php

namespace Oro\Bundle\ActivityBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Twig\TabExtension;

class MenuActivityWidgetProvider implements ActivityWidgetProviderInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var TabExtension */
    protected $widgetProvider;

    /** @var string */
    protected $menuName;

    /** @var string */
    protected $entityClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param TabExtension   $widgetProvider
     * @param string         $menuName
     * @param string         $entityClass
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        TabExtension $widgetProvider,
        $menuName,
        $entityClass
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->widgetProvider = $widgetProvider;
        $this->menuName       = $menuName;
        $this->entityClass    = $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity)
    {
        return is_a($entity, $this->entityClass);
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgets($entity)
    {
        $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);

        return $this->widgetProvider->getTabs($this->menuName, ['id' => $entityId]);
    }
}
