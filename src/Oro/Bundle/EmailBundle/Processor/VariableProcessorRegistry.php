<?php

namespace Oro\Bundle\EmailBundle\Processor;

use Oro\Bundle\EmailBundle\Exception\UnknownVariableProcessorException;
use Oro\Component\DependencyInjection\Exception\UnknownAliasException;
use Oro\Component\DependencyInjection\ServiceLinkRegistryAwareInterface;
use Oro\Component\DependencyInjection\ServiceLinkRegistryAwareTrait;

class VariableProcessorRegistry implements ServiceLinkRegistryAwareInterface
{
    use ServiceLinkRegistryAwareTrait;

    /**
     * @param string $alias
     *
     * @return VariableProcessorInterface
     */
    public function get($alias)
    {
        try {
            $processor = $this->serviceLinkRegistry->get($alias);
        } catch (UnknownAliasException $parent) {
            throw new UnknownVariableProcessorException($alias, $parent);
        }

        if (!$processor instanceof VariableProcessorInterface) {
            throw new \DomainException(
                sprintf(
                    'Variable processor `%s` with `%s` alias must implement %s.',
                    get_class($processor),
                    $alias,
                    VariableProcessorInterface::class
                )
            );
        }

        return $processor;
    }
}
