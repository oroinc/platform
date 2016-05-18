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
        $this->throwAllowedTypesException();
    }

    /**
     * {@inheritdoc}
     */
    public function addAllowedTypes($option, $allowedTypes = null)
    {
        $this->throwAllowedTypesException();
    }

    protected function throwAllowedTypesException()
    {
        throw new LogicException(
            'Oro\Component\Layout\Block\OptionsResolver\OptionsResolver::setAllowedTypes method call is denied'
        );
    }
}
