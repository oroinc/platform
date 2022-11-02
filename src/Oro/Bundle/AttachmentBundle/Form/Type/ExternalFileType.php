<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\DataTransformer\ExternalFileTransformer;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents a form type for {@see ExternalFile}.
 */
class ExternalFileType extends AbstractType
{
    private ExternalFileTransformer $externalFileTransformer;

    public function __construct(ExternalFileTransformer $externalFileTransformer)
    {
        $this->externalFileTransformer = $externalFileTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer($this->externalFileTransformer);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['attr']['data-is-external-file'] = 1;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExternalFile::class,
            'empty_data' => null,
        ]);
    }

    public function getParent(): string
    {
        return UrlType::class;
    }
}
