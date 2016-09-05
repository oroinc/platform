<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WidgetItemsType extends AbstractType
{
    const NAME = 'oro_type_widget_items';

    /** @var EventSubscriberInterface */
    private $itemsSubscriber;

    /**
     * @param EventSubscriberInterface $itemsSubscriber
     */
    public function __construct(EventSubscriberInterface $itemsSubscriber)
    {
        $this->itemsSubscriber = $itemsSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->itemsSubscriber);

        $builder->add('items', 'collection', [
            'type' => 'oro_type_widget_item',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'widget_name',
            'item_label',
        ]);

        $resolver->setAllowedTypes([
            'widget_name' => 'string',
            'item_label'  => 'string',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['item_label'] = $options['item_label'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }
}
