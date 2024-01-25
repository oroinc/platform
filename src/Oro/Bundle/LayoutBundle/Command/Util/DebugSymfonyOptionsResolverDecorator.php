<?php

namespace Oro\Bundle\LayoutBundle\Command\Util;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Decorator for symfony option resolver that helps to get information about all registered options.
 */
class DebugSymfonyOptionsResolverDecorator extends DebugOptionsResolverDecorator
{
    public function __construct(OptionsResolver $optionsResolver)
    {
        $this->optionsResolver = $optionsResolver;
    }

    public function getDefaultOptions(): array
    {
        return $this->getPrivatePropertyValue($this->optionsResolver, 'defaults');
    }

    public function getRequiredOptions(): array
    {
        return $this->getPrivatePropertyValue($this->optionsResolver, 'required');
    }

    public function getDefinedOptions(): array
    {
        return array_keys($this->getPrivatePropertyValue($this->optionsResolver, 'defined'));
    }
}
