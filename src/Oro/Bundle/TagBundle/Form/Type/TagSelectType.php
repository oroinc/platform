<?php
namespace Oro\Bundle\TagBundle\Form\Type;

use Oro\Bundle\TagBundle\Form\EventSubscriber\TagSubscriber;
use Oro\Bundle\TagBundle\Form\Transformer\TagTransformer;
use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagSelectType extends AbstractType
{
    /**
     * @var TagSubscriber
     */
    protected $subscriber;

    /**
     * @var TagTransformer
     */
    protected $transformer;

    public function __construct(TagSubscriber $subscriber, TagTransformer $transformer)
    {
        $this->subscriber = $subscriber;
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'required'     => false,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->subscriber);

        $builder->add(
            'autocomplete',
            'oro_tag_autocomplete'
        );

        $builder->add(
            $builder->create(
                'all',
                'hidden'
            )->addViewTransformer($this->transformer)
        );

        $builder->add(
            $builder->create(
                'owner',
                'hidden'
            )->addViewTransformer($this->transformer)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_tag_select';
    }
}
