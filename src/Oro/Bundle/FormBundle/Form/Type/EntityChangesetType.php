<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToJsonTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityChangesetTransformer;

class EntityChangesetType extends AbstractType
{
    const NAME = 'oro_entity_changeset';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['class']);
        $resolver->setDefaults(['mapped' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addViewTransformer(new EntityChangesetTransformer($this->doctrineHelper, $options['class']))
            ->addViewTransformer(new ArrayToJsonTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'hidden';
    }
}
