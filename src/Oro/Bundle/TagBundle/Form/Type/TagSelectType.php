<?php
namespace Oro\Bundle\TagBundle\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\TagBundle\Form\Transformer\TagTransformer;
use Oro\Bundle\TagBundle\Form\EventSubscriber\TagSubscriber;

class TagSelectType extends AbstractType
{
    /** @var TagSubscriber */
    protected $subscriber;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var TagTransformer */
    protected $tagTransformer;

    /**
     * @param SecurityFacade $securityFacade
     * @param TagTransformer $tagTransformer
     * @param TagSubscriber  $subscriber
     */
    public function __construct(
        SecurityFacade $securityFacade,
        TagTransformer $tagTransformer,
        TagSubscriber $subscriber
    ) {
        $this->securityFacade = $securityFacade;
        $this->tagTransformer = $tagTransformer;
        $this->subscriber     = $subscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetViewTransformers();
        $builder->addViewTransformer($this->tagTransformer);
        $builder->addEventSubscriber($this->subscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['component_options']['oro_tag_create_granted'] = $this->securityFacade->isGranted('oro_tag_create');
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'required'           => false,
                'configs'            => [
                    'placeholder'             => 'oro.tag.form.choose_or_create_tag',
                    'component'               => 'multi-autocomplete',
                    'multiple'                => true,
                    'result_template_twig'    => 'OroTagBundle:Tag:Autocomplete/result.html.twig',
                    'selection_template_twig' => 'OroTagBundle:Tag:Autocomplete/selection.html.twig',
                    'properties'              => ['id', 'name'],
                    'separator'               => ';;',
                ],
                'autocomplete_alias' => 'tags'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_tag_select';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_jqueryselect2_hidden';
    }
}
