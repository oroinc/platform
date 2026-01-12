<?php

namespace Oro\Component\Action;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides lazy-initialized options resolution using Symfony's OptionsResolver.
 *
 * This trait manages a singleton {@see OptionsResolver} instance that is configured on first use
 * by calling the abstract {@see configureOptions()} method. It caches the resolver to avoid
 * reconfiguration on subsequent {@see resolve()} calls, improving performance for repeated option validation.
 * Classes using this trait must implement the {@see configureOptions()} abstract method to define
 * their specific option requirements and defaults.
 */
trait OptionsResolverTrait
{
    /** @var OptionsResolver */
    private $resolver;

    /**
     * Internally calls configureOptions(OptionsResolver $resolver) abstract method if resolver was not instantiated yet
     * @param array $options
     * @return array Resolved options
     */
    protected function resolve(array $options)
    {
        if (!$this->resolver) {
            $this->resolver = new OptionsResolver();
            $this->configureOptions($this->resolver);
        }

        return $this->resolver->resolve($options);
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    abstract protected function configureOptions(OptionsResolver $resolver);
}
