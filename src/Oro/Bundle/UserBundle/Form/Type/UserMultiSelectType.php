<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that provides choice several users
 */
class UserMultiSelectType extends AbstractType
{
    const NAME = 'oro_user_multiselect';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(
            new EntitiesToIdsTransformer($this->entityManager, $options['entity_class'])
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'autocomplete_alias'  => 'users',
                'configs'             => array(
                    'multiple'                   => true,
                    'placeholder'                => 'oro.user.form.choose_user',
                    'allowClear'                 => true,
                    'result_template_twig'       => '@OroUser/User/Autocomplete/result.html.twig',
                    'selection_template_twig'    => '@OroUser/User/Autocomplete/selection.html.twig',
                )
            )
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroJquerySelect2HiddenType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
