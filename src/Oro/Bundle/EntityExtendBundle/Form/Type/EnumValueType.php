<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\EntityExtendBundle\Validator\Constraints as ExtendAssert;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;

class EnumValueType extends AbstractType
{
    /** @var EnumTypeHelper */
    protected $typeHelper;

    /**
     * @param EnumTypeHelper $typeHelper
     */
    public function __construct(EnumTypeHelper $typeHelper)
    {
        $this->typeHelper = $typeHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', 'hidden')
            ->add('label', 'text', [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('is_default', 'checkbox', ['required' => false])
            ->add('priority', 'hidden', ['empty_data' => 9999]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['allow_delete'] = $this->isDeletable($form);
    }

    /**
     * Checks that the option can be removed
     *
     * @param FormInterface $form
     * @return boolean
     */
    protected function isDeletable(FormInterface $form)
    {
        $data = $form->getData();

        if (!$data) {
            return true;
        }

        /** @var ConfigIdInterface $configId */
        $configId = $form->getParent()->getConfig()->getOption('config_id');
        $className = $configId->getClassName();

        if (empty($className)) {
            return true;
        }

        $fieldName = $this->typeHelper->getFieldName($configId);
        if (empty($fieldName)) {
            return true;
        }

        $enumCode = $this->typeHelper->getEnumCode($className, $fieldName);
        if (empty($enumCode)) {
            return true;
        }

        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
        $immutable = $this->typeHelper->getImmutableCodes('enum', $enumValueClassName);

        if (is_array($immutable) && in_array($data['id'], $immutable)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'constraints' => [
                new ExtendAssert\EnumValue(),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_extend_enum_value';
    }
}
