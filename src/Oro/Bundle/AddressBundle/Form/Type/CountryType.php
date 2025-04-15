<?php

namespace Oro\Bundle\AddressBundle\Form\Type;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting Country entity
 */
class CountryType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'label' => 'oro.address.country.entity_label',
                'class' => Country::class,
                'random_id' => true,
                'query_builder' => function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('c');

                    return $qb
                        ->where($qb->expr()->eq('c.deleted', ':deleted'))
                        ->orderBy('c.name', 'ASC')
                        ->setParameter('deleted', false, Types::BOOLEAN);
                },
                'choice_label' => 'name',
                'configs' => array(
                    'allowClear' => true,
                    'placeholder'   => 'oro.address.form.choose_country'
                ),
                'placeholder' => '',
                'empty_data'  => null
            )
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return Select2TranslatableEntityType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_country';
    }
}
