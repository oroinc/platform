<?php

namespace Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type;

use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessorInterface;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Exception\UnexpectedTypeException;
use Oro\Component\Layout\Util\BlockUtils;

abstract class AbstractFormType extends AbstractType
{
    const SHORT_NAME = '';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(
                [
                    'form' => null,
                    'form_name' => 'form',
                    'instance_name' => '',
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        BlockUtils::setViewVarsFromOptions($view, $options, ['form', 'form_name', 'instance_name']);
    }

    /**
     * Returns the form accessor.
     *
     * @param ContextInterface $context
     * @param Options|array    $options
     *
     * @return FormAccessorInterface
     *
     * @throws \OutOfBoundsException if the context does not contain the form accessor
     * @throws UnexpectedTypeException if the form accessor stored in the context has invalid type
     */
    protected function getFormAccessor(ContextInterface $context, $options)
    {
        /** @var FormAccessorInterface $formAccessor */
        if (!empty($options['form'])) {
            $formAccessor = $options['form'];
            if (!$formAccessor instanceof FormAccessorInterface) {
                throw new UnexpectedTypeException(
                    $formAccessor,
                    'Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessorInterface',
                    'options[form]'
                );
            }
        } else {
            $formAccessor = $context->get($options['form_name']);
            if (!$formAccessor instanceof FormAccessorInterface) {
                throw new UnexpectedTypeException(
                    $formAccessor,
                    'Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessorInterface',
                    sprintf('context[%s]', $options['form_name'])
                );
            }
        }

        return $formAccessor;
    }
}
