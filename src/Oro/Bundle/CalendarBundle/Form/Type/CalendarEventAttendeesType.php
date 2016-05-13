<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CalendarBundle\Form\DataTransformer\UsersToAttendeesTransformer;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;

class CalendarEventAttendeesType extends AbstractType
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var DataTransformerInterface */
    protected $usersToAttendeesTransformer;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->usersToAttendeesTransformer = new UsersToAttendeesTransformer(
            new EntitiesToIdsTransformer(
                $this->registry->getManagerForClass($options['entity_class']),
                $options['entity_class']
            )
        );

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
            'configs' => function (Options $options, $value) {
                return array_merge(
                    $value,
                    [
                        'allowCreateNew' => true,
                        'renderedPropertyName' => 'email',
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
            $transformedData = $this->usersToAttendeesTransformer->transform($formData);

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
