<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContext;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumValueType extends AbstractType
{
    const INVALID_NAME_MESSAGE =
        'This value should contain only alphabetic symbols, underscore, hyphen, spaces and numbers.';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', 'hidden')
            ->add('label', 'text', ['required' => true])
            ->add('is_default', 'checkbox', ['required' => false])
            ->add('priority', 'hidden', ['empty_data' => 9999]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSetData']);
    }

    /**
     * POST_SET_DATA event handler
     *
     * @param FormEvent $event
     */
    public function postSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        $constraints = [
            new Assert\NotBlank(),
            new Assert\Length(['max' => 255])
        ];
        if (empty($data['id'])) {
            $callback = function ($value, ExecutionContext $context) {
                if (!empty($value)) {
                    $id = ExtendHelper::buildEnumValueId($value, false);
                    if (empty($id)) {
                        $context->addViolation(self::INVALID_NAME_MESSAGE, ['{{ value }}' => $value]);
                    }
                }
            };

            $constraints[] = new Assert\Callback([$callback]);
        }

        $form->add(
            'label',
            'text',
            [
                'required'    => true,
                'constraints' => $constraints
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_extend_enum_value';
    }
}
