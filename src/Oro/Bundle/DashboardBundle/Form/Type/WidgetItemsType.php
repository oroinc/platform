<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WidgetItemsType extends AbstractType
{
    public const NAME = 'oro_type_widget_items';

    /** @var EventSubscriberInterface */
    private $itemsSubscriber;

    public function __construct(EventSubscriberInterface $itemsSubscriber)
    {
        $this->itemsSubscriber = $itemsSubscriber;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->itemsSubscriber);

        $builder->add('items', CollectionType::class, [
            'entry_type' => WidgetItemType::class,
        ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'widget_name',
            'item_label',
        ]);

        $resolver->setAllowedTypes('widget_name', 'string');
        $resolver->setAllowedTypes('item_label', 'string');
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['item_label'] = $options['item_label'];
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}
