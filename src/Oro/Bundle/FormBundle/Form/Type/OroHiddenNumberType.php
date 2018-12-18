<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type which repeats behavior of NumberType, but is rendered as hidden input.
 *
 * Makes it possible to pass and receive numeric form data formatted according to current locale.
 */
class OroHiddenNumberType extends AbstractType
{
    private const NAME = 'oro_hidden_number';

    /**
     * @var NumberFormatter
     */
    private $numberFormatter;

    /**
     * @param NumberFormatter $numberFormatter
     */
    public function __construct(NumberFormatter $numberFormatter)
    {
        $this->numberFormatter = $numberFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['type'] = 'hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'grouping' => (bool)$this->numberFormatter->getAttribute(\NumberFormatter::GROUPING_USED),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return NumberType::class;
    }
}
