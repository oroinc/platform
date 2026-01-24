<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for managing collections of widget items.
 *
 * This form type provides a complete interface for configuring multiple widget items,
 * including their visibility, order, and other properties. It uses a collection of
 * {@see WidgetItemType} forms and integrates with event subscribers to handle dynamic item
 * management. The form requires widget name and item label configuration to properly
 * render and process the item collection.
 */
class WidgetItemsType extends AbstractType
{
    const NAME = 'oro_type_widget_items';

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
