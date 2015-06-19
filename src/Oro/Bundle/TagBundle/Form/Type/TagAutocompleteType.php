<?php
namespace Oro\Bundle\TagBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class TagAutocompleteType extends AbstractType
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade       $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'configs' => array(
                    'placeholder'    => 'oro.tag.form.choose_or_create_tag',
                    'component'   => 'multi-autocomplete',
                    'multiple'       => true
                ),
                'autocomplete_alias' => 'tags'
            )
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
