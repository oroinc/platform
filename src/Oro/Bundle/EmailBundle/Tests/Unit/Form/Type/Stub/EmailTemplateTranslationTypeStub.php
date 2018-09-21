<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateTranslationType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Stub class for EmailTemplateTranslationType to avoid using a2lix form types in form integration tests.
 */
class EmailTemplateTranslationTypeStub extends EmailTemplateTranslationType
{
    public function getParent()
    {
        return TextType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'content_options' => [],
            'labels' => [],
            'locales' => [],
        ]);
    }
}
