<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;

/**
 * @deprecated since 1.9. Use EntityClassParameterFilter instead
 */
class HttpEntityNameParameterFilter implements ParameterFilterInterface
{
    /** @var EntityRoutingHelper */
    protected $helper;

    /**
     * @param EntityRoutingHelper $helper
     */
    public function __construct(EntityRoutingHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function filter($rawValue, $operator)
    {
        return $this->helper->resolveEntityClass($rawValue);
    }
}
