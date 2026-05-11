<?php

namespace Oro\Bundle\IntegrationBundle\Api\Form\Type;

use Oro\Bundle\IntegrationBundle\Api\Form\DataTransformer\WebhookFormatAssociationDataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type that represents to-one association
 * to {@see \Oro\Bundle\IntegrationBundle\Api\Model\WebhookFormat}.
 */
class WebhookFormatAssociationType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer(new WebhookFormatAssociationDataTransformer());
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('compound', false)
            ->setDefault('multiple', true);
    }
}
