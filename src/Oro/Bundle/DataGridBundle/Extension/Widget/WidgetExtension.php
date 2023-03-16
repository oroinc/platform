<?php

namespace Oro\Bundle\DataGridBundle\Extension\Widget;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Add widget ID for datagrid if a route passing via a widget.
 */
class WidgetExtension extends AbstractExtension
{
    public const WIDGET_ID_PARAM = 'widgetId';

    public function __construct(protected RequestStack $requestStack)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $data->offsetAddToArray('state', [
                self::WIDGET_ID_PARAM => (int)$request->query->get('_widgetId', null)
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request && $request->query->get('_widgetId', null);
    }
}
