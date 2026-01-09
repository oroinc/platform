<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Exception\InvalidParameterException;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Marks one or more datagrids for refresh in the current context.
 *
 * This action adds grid names to a special context property that signals the frontend to refresh
 * the specified datagrids. It supports multiple grid names and merges them with any existing refresh
 * requests, ensuring each grid is refreshed only once.
 */
class RefreshGrid extends AbstractAction
{
    /**
     * @var array
     */
    protected $gridNames;

    #[\Override]
    protected function executeAction($context)
    {
        $property = new PropertyPath('refreshGrid');

        $gridNames = $this->contextAccessor->getValue($context, $property);
        $gridNames = array_map(
            function ($gridName) use ($context) {
                return $this->contextAccessor->getValue($context, $gridName);
            },
            array_merge((array)$gridNames, $this->gridNames)
        );

        $this->contextAccessor->setValue($context, $property, array_unique($gridNames));
    }

    #[\Override]
    public function initialize(array $options)
    {
        if (empty($options)) {
            throw new InvalidParameterException('Gridname parameter must be specified');
        }

        $this->gridNames = $options;

        return $this;
    }
}
