<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EmailBundle\Form\DataTransformer\ContextsTransformer;

class ContextsSelectType extends AbstractType
{
    const NAME = 'oro_email_contexts_select';

    /**
     * @var EntityManager
     */
    protected $entityManager;

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
            new ContextsTransformer($this->entityManager)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'tooltip' => false,
                'configs' => [
                    'placeholder'   => 'oro.email.contexts.placeholder',
                    'allowClear'    => true,
                    'multiple'      => true,
                    'route_name'    => 'oro_api_get_search_autocomplete',
                    'separator'     => ';',
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
