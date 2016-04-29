<?php

namespace Oro\Component\Action;

use Symfony\Component\OptionsResolver\OptionsResolver;

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
