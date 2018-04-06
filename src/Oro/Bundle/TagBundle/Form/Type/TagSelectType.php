<?php
namespace Oro\Bundle\TagBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\TagBundle\Form\EventSubscriber\TagSubscriber;
use Oro\Bundle\TagBundle\Form\Transformer\TagTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TagSelectType extends AbstractType
{
    /** @var TagSubscriber */
    protected $subscriber;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TagTransformer */
    protected $tagTransformer;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TagTransformer                $tagTransformer
     * @param TagSubscriber                 $subscriber
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TagTransformer $tagTransformer,
        TagSubscriber $subscriber
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tagTransformer = $tagTransformer;
        $this->subscriber = $subscriber;
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
        $view->vars['component_options']['oro_tag_create_granted'] =
            $this->authorizationChecker->isGranted('oro_tag_create');
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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
        return OroJquerySelect2HiddenType::class;
    }
}
