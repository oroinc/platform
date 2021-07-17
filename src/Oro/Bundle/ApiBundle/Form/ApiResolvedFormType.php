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

/**
 * A wrapper for an API form type and its extensions.
 */
class ApiResolvedFormType implements ResolvedFormTypeInterface
{
    /** @var ResolvedFormTypeInterface */
    private $innerType;

    public function __construct(ResolvedFormTypeInterface $innerType)
    {
        $this->innerType = $innerType;
    }

    public function getBlockPrefix()
    {
        return $this->innerType->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->innerType->getParent();
    }

    /**
     * {@inheritdoc}
     */
    public function getInnerType()
    {
        return $this->innerType->getInnerType();
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeExtensions()
    {
        return $this->innerType->getTypeExtensions();
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsResolver()
    {
        return $this->innerType->getOptionsResolver();
    }

    /**
     * {@inheritdoc}
     */
    public function createBuilder(FormFactoryInterface $factory, $name, array $options = [])
    {
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
     * {@inheritdoc}
     */
    public function createView(FormInterface $form, FormView $parent = null)
    {
        return $this->innerType->createView($form, $parent);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->innerType->buildForm($builder, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $this->innerType->buildView($view, $form, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $this->innerType->finishView($view, $form, $options);
    }
}
