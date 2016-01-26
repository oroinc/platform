<?php
namespace Oro\Bundle\TagBundle\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\TagBundle\Form\Transformer\TagTransformer;

class TagAutocompleteType extends AbstractType
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var TagTransformer */
    protected $tagTransformer;

    /**
     * @param SecurityFacade $securityFacade
     * @param TagTransformer $tagTransformer
     */
    public function __construct(SecurityFacade $securityFacade, TagTransformer $tagTransformer)
    {
        $this->securityFacade = $securityFacade;
        $this->tagTransformer = $tagTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetViewTransformers();
        $builder->addViewTransformer($this->tagTransformer);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
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
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['component_options']['oro_tag_create_granted'] = $this->securityFacade->isGranted('oro_tag_create');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_tag_autocomplete';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_jqueryselect2_hidden';
    }
}
