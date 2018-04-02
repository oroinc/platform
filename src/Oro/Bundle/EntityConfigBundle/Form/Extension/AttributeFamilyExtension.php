<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamilyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

class AttributeFamilyExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;

    /**
     * @var ConfigProvider
     */
    protected $attributeConfigProvider;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param ConfigProvider $attributeConfigProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ConfigProvider $attributeConfigProvider, DoctrineHelper $doctrineHelper)
    {
        $this->attributeConfigProvider = $attributeConfigProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isApplicable($options)) {
            return;
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $class = $event->getForm()->getConfig()->getOptions()['data_class'];

        $repository = $this->doctrineHelper->getEntityRepositoryForClass(AttributeFamily::class);
        $families = $repository->findBy(['entityClass' => $class]);
        $event->getForm()->add(
            'attributeFamily',
            EntityType::class,
            [
                'class' => AttributeFamily::class,
                'choices' => $families,
                'label' => 'oro.entity_config.attribute_family.entity_label',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ]
            ]
        );
    }

    /**
     * @param array $options
     * @return bool
     */
    protected function isApplicable(array $options)
    {
        if (empty($options['data_class'])) {
            return false;
        }

        if (empty($options['enable_attribute_family'])) {
            return false;
        }

        if (!is_a($options['data_class'], AttributeFamilyAwareInterface::class, true)) {
            return false;
        }

        if (!$this->attributeConfigProvider->hasConfig($options['data_class'])) {
            return false;
        }

        $attributeConfig = $this->attributeConfigProvider->getConfig($options['data_class']);
        if (!$attributeConfig->is('has_attributes')) {
            return false;
        }

        return true;
    }
}
