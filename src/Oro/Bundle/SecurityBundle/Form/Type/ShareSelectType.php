<?php

namespace Oro\Bundle\SecurityBundle\Form\Type;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToJsonTransformer;

class ShareSelectType extends AbstractType
{
    /**
     * @var EntityManager
     */
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
        $builder->resetViewTransformers();
        $builder->addViewTransformer(new EntitiesToJsonTransformer($this->entityManager));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'configs' => [
                    'placeholder' => 'oro.security.action.share_with_placeholder',
                    'allowClear' => true,
                    'multiple' => true,
                    'route_name' => 'oro_api_get_share_sharing_entities',
                    'separator' => ';',
                    'minimumInputLength' => 1,
                    'component' => 'share',
                ],
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
        return 'oro_share_select';
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['entityClass'] = $form->getParent()->get('entityClass')->getData();
    }
}
