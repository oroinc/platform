<?php

namespace Oro\Bundle\ConfigBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\ConfigBundle\Form\DataTransformer\ConfigFileDataTransformer;
use Symfony\Component\Validator\Constraints\Image;

class ConfigFileType extends FileType
{
    const NAME = 'oro_config_file';

    /**
     * @var ConfigFileDataTransformer
     */
    private $transformer;

    public function __construct(ConfigFileDataTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'file',
            'file',
            [
                'label' => 'oro.attachment.file.label',
                'constraints' => [
                    new Image()
                ]
            ]
        );

        $builder
            ->add(
                'emptyFile',
                HiddenType::class,
                [
                    'required' => false,
                ]
            );

        $builder->addModelTransformer($this->transformer);
        $builder->addEventSubscriber($this->eventSubscriber);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
