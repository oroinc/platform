<?php

namespace Oro\Bundle\DashboardBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\NameStrategy as BaseNameStrategy;
use Symfony\Component\HttpFoundation\RequestStack;

class NameStrategy extends BaseNameStrategy
{
    /** @var RequestStack */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getGridUniqueName($name)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return $name;
        }

        $uniqueName = $name;
        if ($widgetId = $request->get('_widgetId')) {
            $uniqueName = sprintf('%s_w%s', $uniqueName, $widgetId);
        } elseif ($request->query->count() === 1) {
            $paramName = array_keys($request->query->all())[0];
            if (strpos($paramName, $name) === 0) {
                $uniqueName = $paramName;
            }
        }

        return $uniqueName;
    }
}
