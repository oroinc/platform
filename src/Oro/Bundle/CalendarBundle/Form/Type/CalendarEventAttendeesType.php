<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CalendarBundle\Form\DataTransformer\UsersToAttendeesTransformer;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;

class CalendarEventAttendeesType extends AbstractType
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var UsersToAttendeesTransformer */
    protected $usersToAttendeesTransformer;

    /** @var AttendeeManager */
    protected $attendeeManager;

    /**
     * @param UsersToAttendeesTransformer $usersToAttendeesTransformer
     * @param AttendeeManager $attendeeManager
     */
    public function __construct(
        UsersToAttendeesTransformer $usersToAttendeesTransformer,
        AttendeeManager $attendeeManager
    ) {
        $this->usersToAttendeesTransformer = $usersToAttendeesTransformer;
        $this->attendeeManager = $attendeeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetModelTransformers();
        $builder->addModelTransformer($this->usersToAttendeesTransformer);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();
            if (!$data) {
                return;
            }

            $invalidAttendees = array_diff(
                $this->parseNewValues($data),
                $this->parseNewValues($form->getViewData())
            );

            if (!$invalidAttendees) {
                return;
            }

            $form->addError(new FormError(sprintf(
                'This field has invalid attendees: "%s"',
                implode(
                    ', ',
                    array_map(
                        function ($value) {
                            return json_decode($value)->value;
                        },
                        $invalidAttendees
                    )
                )
            )));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'autocomplete_alias' => 'organization_all_users',
            'layout_template' => false,
            'configs' => function (Options $options, $value) {
                $newConfigs = [
                    'renderedPropertyName' => 'email',
                    'forceSelectedData' => true,
                ];

                if ($options['layout_template']) {
                    $newConfigs['component'] = 'attendees';
                }

                return array_merge($value, $newConfigs);
            },
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $formData = $form->getData();
        if (!$formData) {
            return;
        }

        $view->vars['attr']['data-selected-data'] = json_encode(
            $this->attendeeManager->attendeesToAutocompleteData($formData)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_user_multiselect';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_calendar_event_attendees';
    }

    /**
     * @param string $value
     *
     * @return array
     */
    protected function parseNewValues($value)
    {
        return array_filter(
            explode(',', $value),
            function ($value) {
                return !is_numeric($value);
            }
        );
    }
}
