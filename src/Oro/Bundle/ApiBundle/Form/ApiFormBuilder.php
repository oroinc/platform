<?php

namespace Oro\Bundle\ApiBundle\Form;

use Oro\Bundle\ApiBundle\Form\DataTransformer\NullTransformer;
use Oro\Bundle\ApiBundle\Form\DataTransformer\NullValueTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormConfigBuilderInterface;

/**
 * A builder for creating API Form instances.
 * This builder adds NullTransformer to correct handling of NULL and empty string values.
 * It is required because by default Symfony Forms replaces NULL with empty string.
 */
class ApiFormBuilder extends FormBuilder
{
    private ?array $emptyViewTransformers = null;

    /**
     * {@inheritDoc}
     */
    public function addViewTransformer(
        DataTransformerInterface $viewTransformer,
        bool $forcePrepend = false
    ): FormConfigBuilderInterface {
        $this->emptyViewTransformers = null;

        if (!$viewTransformer instanceof NullValueTransformer) {
            $viewTransformer = new NullValueTransformer($viewTransformer);
        }

        return parent::addViewTransformer($viewTransformer, $forcePrepend);
    }

    /**
     * {@inheritDoc}
     */
    public function resetViewTransformers(): FormConfigBuilderInterface
    {
        $this->emptyViewTransformers = null;

        return parent::resetViewTransformers();
    }

    /**
     * {@inheritDoc}
     */
    public function getViewTransformers(): array
    {
        $viewTransformers = parent::getViewTransformers();
        // if a field does not have any view transformer use a transformer that does nothing,
        // it is required because by default Symfony Forms replaces NULL with empty string
        if (empty($viewTransformers)) {
            if (null === $this->emptyViewTransformers) {
                $this->emptyViewTransformers = [NullTransformer::getInstance()];
            }
            $viewTransformers = $this->emptyViewTransformers;
        }

        return $viewTransformers;
    }
}
