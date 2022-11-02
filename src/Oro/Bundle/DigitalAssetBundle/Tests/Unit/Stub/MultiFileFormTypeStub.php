<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Stub;

use Oro\Bundle\AttachmentBundle\Form\Type\MultiFileType;
use Oro\Bundle\DigitalAssetBundle\Tests\Unit\Stub\Entity\EntityWithMultiFile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MultiFileFormTypeStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('multiFileField', MultiFileType::class, []);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EntityWithMultiFile::class,
        ]);
    }
}
