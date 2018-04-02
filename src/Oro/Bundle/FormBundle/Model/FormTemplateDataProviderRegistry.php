<?php

namespace Oro\Bundle\FormBundle\Model;

use Oro\Bundle\FormBundle\Exception\UnknownProviderException;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Oro\Component\DependencyInjection\Exception\UnknownAliasException;
use Oro\Component\DependencyInjection\ServiceLinkRegistryAwareInterface;
use Oro\Component\DependencyInjection\ServiceLinkRegistryAwareTrait;

/**
 * Registry of form template data provider services by their aliases.
 *
 * Has late construction (instantiation) mechanism (internal resolving from service links registry),
 * as all instances of providers not needed in a single request runtime.
 */
class FormTemplateDataProviderRegistry implements ServiceLinkRegistryAwareInterface
{
    const DEFAULT_PROVIDER_NAME = 'default';

    use ServiceLinkRegistryAwareTrait;

    /**
     * @param string $alias
     *
     * @return FormTemplateDataProviderInterface
     */
    public function get($alias)
    {
        try {
            $provider = $this->serviceLinkRegistry->get($alias);
        } catch (UnknownAliasException $parent) {
            throw new UnknownProviderException($alias, $parent);
        }

        if (!$provider instanceof FormTemplateDataProviderInterface) {
            throw new \DomainException(
                sprintf(
                    'Form data provider `%s` with `%s` alias must implement %s.',
                    get_class($provider),
                    $alias,
                    FormTemplateDataProviderInterface::class
                )
            );
        }

        return $provider;
    }
}
