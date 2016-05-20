<?php

namespace Oro\Component\Layout\Block\OptionsResolver;

use Symfony\Component\OptionsResolver\OptionsResolver as BaseOptionsResolver;

use Oro\Component\Layout\Exception\LogicException;

class OptionsResolver extends BaseOptionsResolver
{
    /**
     * {@inheritdoc}
     */
    public function setAllowedTypes($option, $allowedTypes = null)
    {
        $this->throwAllowedTypesException(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function addAllowedTypes($option, $allowedTypes = null)
    {
        $this->throwAllowedTypesException(__FUNCTION__);
    }

    /**
     * @param string $methodName
     */
    protected function throwAllowedTypesException($methodName)
    {
        throw new LogicException(sprintf(
            'Oro\Component\Layout\Block\OptionsResolver\OptionsResolver::%s method call is denied',
            $methodName
        ));
    }
}
