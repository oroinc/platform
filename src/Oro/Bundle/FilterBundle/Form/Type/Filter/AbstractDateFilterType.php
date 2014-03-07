<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\FilterBundle\Form\EventListener\DateFilterSubscriber;

abstract class AbstractDateFilterType extends AbstractType
{
    const TYPE_BETWEEN     = 1;
    const TYPE_NOT_BETWEEN = 2;
    const TYPE_MORE_THAN   = 3;
    const TYPE_LESS_THAN   = 4;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var DateModifierProvider */
    protected $dateModifiers;

    /** @var null|array */
    protected $dateVarsChoices = null;

    /** @var null|array */
    protected $datePartsChoices = null;

    /** @var DateFilterSubscriber */
    protected $subscriber;

    /**
     * @param TranslatorInterface   $translator
     * @param DateModifierInterface $dateModifiers
     * @param DateFilterSubscriber  $subscriber
     */
    public function __construct(
        TranslatorInterface $translator,
        DateModifierInterface $dateModifiers,
        DateFilterSubscriber $subscriber
    ) {
        $this->translator    = $translator;
        $this->dateModifiers = $dateModifiers;
        $this->subscriber    = $subscriber;
    }

    /**
     * @return array
     */
    public static function getTypeValues()
    {
        return [
            'between'    => self::TYPE_BETWEEN,
            'notBetween' => self::TYPE_NOT_BETWEEN,
            'moreThan'   => self::TYPE_MORE_THAN,
            'lessThan'   => self::TYPE_LESS_THAN
        ];
    }

    /**
     * @return array
     */
    public function getOperatorChoices()
    {
        return [
            self::TYPE_BETWEEN     => $this->translator->trans('oro.filter.form.label_date_type_between'),
            self::TYPE_NOT_BETWEEN => $this->translator->trans('oro.filter.form.label_date_type_not_between'),
            self::TYPE_MORE_THAN   => $this->translator->trans('oro.filter.form.label_date_type_more_than'),
            self::TYPE_LESS_THAN   => $this->translator->trans('oro.filter.form.label_date_type_less_than'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
            'date_parts' => $this->getDateParts(),
            'date_vars'  => $this->getDateVariables(),
            ]
        );
    }

    /**
     * @return array|null
     */
    protected function getDateParts()
    {
        if (is_null($this->datePartsChoices)) {
            $t                      = $this->translator;
            $this->datePartsChoices = array_map(
                function ($item) use ($t) {
                    return $t->trans($item);
                },
                $this->dateModifiers->getDateParts()
            );
        }

        return $this->datePartsChoices;
    }

    /**
     * @return array|null
     */
    protected function getDateVariables()
    {
        if (is_null($this->dateVarsChoices)) {
            $t      = $this->translator;
            $result = $this->dateModifiers->getDateVariables();

            foreach ($result as $part => $vars) {
                $result[$part] = array_map(
                    function ($item) use ($t) {
                        return $t->trans($item);
                    },
                    $vars
                );
            }
            $this->dateVarsChoices = $result;
        }

        return $this->dateVarsChoices;
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $widgetOptions                = ['firstDay' => 0];
        $view->vars['widget_options'] = array_merge($widgetOptions, $options['widget_options']);
        $view->vars['date_parts']     = $options['date_parts'];
        $view->vars['date_vars']      = $options['date_vars'];
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!isset($options['date_parts'])) {
            $options['date_parts'] = [];
        }

        $builder->add('part', 'choice', ['choices' => $options['date_parts']]);
        $builder->addEventSubscriber($this->subscriber);
    }
}
