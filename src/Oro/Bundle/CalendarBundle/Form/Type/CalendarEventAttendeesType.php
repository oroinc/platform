<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Form\DataTransformer\UsersToAttendeesTransformer;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;

class CalendarEventAttendeesType extends AbstractType
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var UsersToAttendeesTransformer */
    protected $usersToAttendeesTransformer;

    /**
     * @param UsersToAttendeesTransformer $usersToAttendeesTransformer
     */
    public function __construct(UsersToAttendeesTransformer $usersToAttendeesTransformer)
    {
        $this->usersToAttendeesTransformer = $usersToAttendeesTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetModelTransformers();
        $builder->addModelTransformer($this->usersToAttendeesTransformer);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'autocomplete_alias' => 'organization_users',
            'disable_user_removal' => false,
            'configs' => function (Options $options, $value) {
                return array_merge(
                    $value,
                    [
                        'allowCreateNew' => true,
                        'renderedPropertyName' => 'email',
                        'forceSelectedData' => true,
                    ]
                );
            },
        ]);
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
            $transformedData = $this->usersToAttendeesTransformer->attendeesToUsers($formData);

            $result = [];
            foreach ($transformedData as $k => $item) {
                $converted = $converter->convertItem($item);
                if (($formData[$k]->getOrigin() && $formData[$k]->getOrigin()->getId() !== Attendee::ORIGIN_SERVER) ||
                    ($options['disable_user_removal'] && $formData[$k]->getUser())
                ) {
                    $converted['locked'] = true;
                }

                $result[]  = $converted;
            }

            $view->vars['attr']['data-selected-data'] = json_encode($result);
        }
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
}
