<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityChangesetTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for tracking changes to entity fields.
 *
 * This type captures and represents the changes made to an entity's fields,
 * storing the original and modified values. It extends {@see DataChangesetType} with
 * entity-specific functionality and uses {@see EntityChangesetTransformer} to handle
 * the conversion between entity data and changeset representation.
 */
class EntityChangesetType extends AbstractType
{
    const NAME = 'oro_entity_changeset';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['class']);
        $resolver->setDefault('context', []);
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new EntityChangesetTransformer($this->doctrineHelper, $options['class']), true);
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

    #[\Override]
    public function getParent(): ?string
    {
        return DataChangesetType::class;
    }
}
