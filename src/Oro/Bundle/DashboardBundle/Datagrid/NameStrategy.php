<?php

namespace Oro\Bundle\DashboardBundle\Datagrid;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\DataGridBundle\Datagrid\NameStrategy as BaseNameStrategy;

class NameStrategy extends BaseNameStrategy
{
    /** @var Request */
    protected $request;

    /**
     * {@inheritdoc}
     */
    public function getGridUniqueName($name)
    {
        $uniqueName = $name;
        if ($this->request && $widgetId = $this->request->get('_widgetId')) {
            $uniqueName = sprintf('%s_w%s', $uniqueName, $widgetId);
        } elseif ($this->request && $this->request->query->count() === 1) {
            $paramName = array_keys($this->request->query->all())[0];
            if (strpos($paramName, $name) === 0) {
                $uniqueName = $paramName;
            }
        }

        return $uniqueName;
    }

    /**
     * @param Request|null $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }
}
