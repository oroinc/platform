<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CalendarBundle\Entity\Attendee;

class CalendarEventAttendeesSelectType extends AbstractType
{
    /** @var DataTransformerInterface */
    protected $attendeesToViewTransformer;

    /**
     * @param DataTransformerInterface $attendeesToViewTransformer
     */
    public function __construct(DataTransformerInterface $attendeesToViewTransformer)
    {
        $this->attendeesToViewTransformer = $attendeesToViewTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetViewTransformers();
        $builder->addViewTransformer($this->attendeesToViewTransformer);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['data-selected-data'] = $view->vars['value'];
        $view->vars['excluded'] = array_filter(array_map(
            function (Attendee $attendee) {
                $user = $attendee->getUser();
                if ($user) {
                    return json_encode([
                        'entityClass' => 'Oro\Bundle\UserBundle\Entity\User',
                        'entityId' => $user->getId(),
                    ]);
                }

                return null;
            },
            $form->getData()->toArray()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'tooltip' => false,
            'layout_template' => false,
            'configs' => function (Options $options, $value) {
                $configs = [
                    'placeholder'        => 'oro.user.form.choose_user',
                    'allowClear'         => true,
                    'multiple'           => true,
                    'separator'          => ';',
                    'forceSelectedData'  => true,
                    'minimumInputLength' => 0,
                    'route_name'         => 'oro_calendarevent_autocomplete_attendees',
                    'route_parameters'   => [
                        'name' => 'name',
                    ],
                ];

                if ($options['layout_template']) {
                    $configs['component'] = 'attendees';
                }

                return $configs;
            }
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_calendar_event_attendees_select';
    }
}
