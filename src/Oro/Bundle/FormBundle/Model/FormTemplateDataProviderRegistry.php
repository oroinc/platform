<?php

namespace Oro\Bundle\FormBundle\Model;

use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * The container for form template data providers.
 */
class FormTemplateDataProviderRegistry
{
    public const DEFAULT_PROVIDER_NAME = 'default';

    /** @var ContainerInterface */
    private $providers;

    public function __construct(ContainerInterface $providers)
    {
        $this->providers = $providers;
    }

    public function has(string $alias): bool
    {
        return $this->providers->has($alias);
    }

    public function get(string $alias): FormTemplateDataProviderInterface
    {
        if (!$this->providers->has($alias)) {
            throw new \LogicException(sprintf('Unknown provider with alias "%s".', $alias));
        }

        return $this->providers->get($alias);
    }
}
