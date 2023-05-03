<?php

namespace Oro\Bundle\ApiBundle\Form;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\ButtonTypeInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\Form\SubmitButtonTypeInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A wrapper for an API form type and its extensions.
 */
class ApiResolvedFormType implements ResolvedFormTypeInterface
{
    private ResolvedFormTypeInterface $innerType;

    public function __construct(ResolvedFormTypeInterface $innerType)
    {
        $this->innerType = $innerType;
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return $this->innerType->getBlockPrefix();
    }

    /**
     * {@inheritDoc}
     */
    public function getParent(): ?ResolvedFormTypeInterface
    {
        return $this->innerType->getParent();
    }

    /**
     * {@inheritDoc}
     */
    public function getInnerType(): FormTypeInterface
    {
        return $this->innerType->getInnerType();
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeExtensions(): array
    {
        return $this->innerType->getTypeExtensions();
    }

    /**
     * {@inheritDoc}
     */
    public function createBuilder(
        FormFactoryInterface $factory,
        string $name,
        array $options = []
    ): FormBuilderInterface {
        if ($this->innerType instanceof ButtonTypeInterface) {
            throw new UnexpectedTypeException($this->innerType, FormTypeInterface::class);
        }
        if ($this->innerType instanceof SubmitButtonTypeInterface) {
            throw new UnexpectedTypeException($this->innerType, FormTypeInterface::class);
        }

        try {
            $options = $this->getOptionsResolver()->resolve($options);
        } catch (ExceptionInterface $e) {
            throw new $e(
                sprintf(
                    'An error has occurred resolving the options of the form "%s": ',
                    \get_class($this->getInnerType())
                ) . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        $builder = new ApiFormBuilder(
            $name,
            $options['data_class'] ?? null,
            new EventDispatcher(),
            $factory,
            $options
        );
        $builder->setType($this);

        return $builder;
    }

    /**
     * {@inheritDoc}
     */
    public function createView(FormInterface $form, FormView $parent = null): FormView
    {
        return $this->innerType->createView($form, $parent);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->innerType->buildForm($builder, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $this->innerType->buildView($view, $form, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $this->innerType->finishView($view, $form, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsResolver(): OptionsResolver
    {
        return $this->innerType->getOptionsResolver();
    }
}
