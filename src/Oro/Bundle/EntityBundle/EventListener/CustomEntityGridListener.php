<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Enables DynamicFieldsExtension for the "custom-entity-grid" to add custom fields to this grid.
 * Provides a method to build URLs to be used to view, update and delete a custom entities.
 */
class CustomEntityGridListener
{
    private UrlGeneratorInterface $urlGenerator;
    private EntityClassNameHelper $entityClassNameHelper;
    /** @var DatagridInterface[] */
    private array $visitedDatagrids = [];

    public function __construct(UrlGeneratorInterface $urlGenerator, EntityClassNameHelper $entityClassNameHelper)
    {
        $this->urlGenerator = $urlGenerator;
        $this->entityClassNameHelper = $entityClassNameHelper;
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        $datagrid = $event->getDatagrid();
        $config = $event->getConfig();

        // remember the current datagrid to further usage in getLinkProperty() method
        $this->visitedDatagrids[$datagrid->getName()] = $datagrid;

        // enable DynamicFieldsExtension to add custom fields
        $config->setExtendedEntityClassName($datagrid->getParameters()->get('class_name'));
    }

    public function getLinkProperty(string $gridName, string $keyName, array $node): callable
    {
        if (!isset($node['route'])) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot build callable fo grid "%s" because the "route" option is mandatory.',
                $gridName
            ));
        }

        $route = $node['route'];

        return function (ResultRecord $record) use ($gridName, $route) {
            if (!isset($this->visitedDatagrids[$gridName])) {
                throw new \InvalidArgumentException(sprintf('Cannot get instance of grid "%s".', $gridName));
            }

            return $this->urlGenerator->generate(
                $route,
                [
                    'entityName' => $this->entityClassNameHelper->getUrlSafeClassName(
                        $this->visitedDatagrids[$gridName]->getParameters()->get('class_name')
                    ),
                    'id'         => $record->getValue('id')
                ]
            );
        };
    }
}
