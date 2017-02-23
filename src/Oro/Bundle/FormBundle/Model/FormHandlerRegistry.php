<?php

namespace Oro\Bundle\FormBundle\Model;

use Oro\Component\DependencyInjection\Exception\UnknownAliasException;
use Oro\Component\DependencyInjection\ServiceLinkRegistry;
use Oro\Component\DependencyInjection\ServiceLinkRegistryAwareInterface;

use Oro\Bundle\FormBundle\Exception\UnknownFormHandlerException;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;

/**
 * Registry of form handler services by their aliases.
 *
 * Has late construction (instantiation) mechanism (internal resolving from service links registry),
 * as all instances of handlers not needed in a single request runtime.
 */
class FormHandlerRegistry implements ServiceLinkRegistryAwareInterface
{
    const DEFAULT_HANDLER_NAME = 'default';

    /** @var ServiceLinkRegistry */
    private $serviceLinkRegistry;

    /**
     * @param ServiceLinkRegistry $serviceLinkAliasRegistry
     */
    public function setServiceLinkRegistry(ServiceLinkRegistry $serviceLinkAliasRegistry)
    {
        $this->serviceLinkRegistry = $serviceLinkAliasRegistry;
    }

    /**
     * @param string $alias
     *
     * @return FormHandlerInterface
     */
    public function get($alias)
    {
        try {
            $handler = $this->serviceLinkRegistry->get($alias);
        } catch (UnknownAliasException $parent) {
            throw new UnknownFormHandlerException($alias, $parent);
        }

        if (!$handler instanceof FormHandlerInterface) {
            throw new \DomainException(
                sprintf(
                    'Form data provider `%s` with `%s` alias must implement %s.',
                    get_class($handler),
                    $alias,
                    FormHandlerInterface::class
                )
            );
        }

        return $handler;
    }

    /**
     * @param $alias
     *
     * @return bool
     */
    public function has($alias)
    {
        return $this->serviceLinkRegistry->has($alias);
    }
}
