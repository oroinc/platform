<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamilyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
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
     * @param ConfigProvider $attributeConfigProvider
     */
    public function __construct(ConfigProvider $attributeConfigProvider)
    {
        $this->attributeConfigProvider = $attributeConfigProvider;
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

        $event->getForm()->add(
            'attributeFamily',
            EntityType::class,
            [
                'class' => AttributeFamily::class,
                'query_builder' => function (AttributeFamilyRepository $repository) use ($class) {
                    $qb = $repository->createQueryBuilder('af');
                    $qb->andWhere($qb->expr()->eq('af.entityClass', ':entityClass'));
                    $qb->setParameter('entityClass', $class);

                    return $qb;
                },
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
