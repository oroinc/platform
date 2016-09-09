<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Form\Transformer\BusinessUnitTreeTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;

/**
 * Class BusinessUnitSelectAutocomplete
 * @package Oro\Bundle\OrganizationBundle\Form\Type
 */
class BusinessUnitSelectAutocomplete extends AbstractType
{
    const NAME = 'oro_type_business_unit_select_autocomplete';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /** @var EntityManager */
    protected $entityManager;

    /** @var BusinessUnitManager */
    protected $businessUnitManager;

    /** @var string */
    protected $entityClass;

    /**
     * BusinessUnitSelectAutocomplete constructor.
     *
     * @param EntityManager $entityManager
     * @param $entityClass
     * @param BusinessUnitManager $businessUnitManager
     */
    public function __construct(
        EntityManager $entityManager,
        $entityClass,
        BusinessUnitManager $businessUnitManager
    ) {
        $this->entityManager = $entityManager;
        $this->entityClass = $entityClass;
        $this->businessUnitManager = $businessUnitManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (isset($options['config']['multiple']) &&  $options['config']['multiple'] === true) {
            $builder->addModelTransformer(
                new EntitiesToIdsTransformer($this->entityManager, $this->entityClass)
            );
        } else {
            $builder->resetModelTransformers();
            $builder->addModelTransformer(
                new BusinessUnitTreeTransformer($this->businessUnitManager)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'configs'            => [
                    'multiple'    => true,
                    'width'       => '400px',
                    'component'   => 'tree-autocomplete',
                    'placeholder' => 'oro.dashboard.form.choose_business_unit',
                    'allowClear'  => true,
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
}
