<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;

use Oro\Bundle\CalendarBundle\Form\DataTransformer\EventsToUsersTransformer;

class CalendarEventInviteesType extends AbstractType
{
    const NAME = 'oro_calendar_event_invitees';

    /**
     * @var EventsToUsersTransformer
     */
    protected $eventsToUsersTransformer;

    /**
     * @param EventsToUsersTransformer $eventsToUsersTransformer
     */
    public function __construct(EventsToUsersTransformer $eventsToUsersTransformer)
    {
        $this->eventsToUsersTransformer = $eventsToUsersTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->eventsToUsersTransformer);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['autocomplete_alias' => 'organization_users']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var ConverterInterface $converter */
        $converter = $options['converter'];

        $formData = $form->getData();
        if ($formData) {
            $transformedData = $this->eventsToUsersTransformer->transform($formData);

            $result = [];
            foreach ($transformedData as $item) {
                $result[] = $converter->convertItem($item);
            }

            $view->vars['attr']['data-selected-data'] = json_encode($result);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_user_multiselect';
    }
}
