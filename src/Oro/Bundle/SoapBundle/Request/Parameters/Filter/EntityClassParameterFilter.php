<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;

class EntityClassParameterFilter implements ParameterFilterInterface
{
    /** @var EntityClassNameHelper */
    protected $helper;

    /**
     * @param EntityClassNameHelper $helper
     */
    public function __construct(EntityClassNameHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function filter($rawValue, $operator)
    {
        if (is_array($rawValue)) {
            return array_map(
                function ($val) {
                    return $this->helper->resolveEntityClass($val);
                },
                $rawValue
            );
        } else {
            return $this->helper->resolveEntityClass($rawValue);
        }
    }
}
