<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\EntityExtendBundle\Validator\Constraints as ExtendAssert;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;

class EnumValueType extends AbstractType
{
    /** @var ConfigProviderInterface */
    protected $configProvider;

    /**
     * @param ConfigProviderInterface $configProvider
     */
    public function __construct(ConfigProviderInterface $configProvider)
    {
        $this->configProvider = $configProvider;
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

        /** @var FieldConfigId $configId */
        $configId = $form->getParent()->getConfig()->getOption('config_id');
        if (!$configId instanceof FieldConfigId) {
            return true;
        }

        $className = $configId->getClassName();
        if (empty($className)) {
            return true;
        }

        $fieldName = $configId->getFieldName();
        if (empty($fieldName)) {
            return true;
        }

        if (!$this->configProvider->hasConfig($className, $fieldName)) {
            return true;
        }

        $enumCode = $this->configProvider->getConfig($className, $fieldName)->get('enum_code');
        if (empty($enumCode)) {
            return true;
        }

        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
        $immutable = $this->configProvider->getConfig($enumValueClassName)->get('immutable_codes');

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
