<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;

/**
 * The form type allows to select user assigned business units, not owning business units.
 */
class BusinessUnitAclMultiSelectType extends AbstractType
{
    /** @var EntityManager */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(
            new EntitiesToIdsTransformer($this->entityManager, $options['entity_class'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'user_business_units',
                'configs' => [
                    'permission' => 'VIEW',
                    'entity_name' => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
                    'multiple' => true,
                    'width' => '400px',
                    'placeholder' => 'oro.business_unit.form.choose_business_user',
                    'allowClear' => true,
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_jqueryselect2_hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_business_unit_multiselect';
    }
}
