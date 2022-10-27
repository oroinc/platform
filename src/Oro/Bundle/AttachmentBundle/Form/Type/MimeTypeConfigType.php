<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\DataTransformer\MimeTypesToStringTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The base class for form types to select allowed MIME types.
 * The model value for the list of MIME types should be a string contains MIME types
 * delimited by linefeed (\n) symbol.
 */
abstract class MimeTypeConfigType extends AbstractType
{
    /** @var string[] */
    private $allowedMimeTypes;

    /**
     * @param string[] $allowedMimeTypes
     */
    public function __construct(array $allowedMimeTypes)
    {
        $this->allowedMimeTypes = $allowedMimeTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new MimeTypesToStringTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices'  => array_combine($this->allowedMimeTypes, $this->allowedMimeTypes),
            'configs'  => [
                'placeholder' => 'oro.attachment.mimetypes.placeholder'
            ],
            'multiple' => true
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OroChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
