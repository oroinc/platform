<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Form\DataTransformer\WebhookConsumerSettingsDataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents the webhook consumer settings as a form field.
 *
 * Provides a functionality to view and to copy a webhook URL that will be used to receive notifications
 * from the remote system and process them by a configured webhook processor.
 */
class WebhookFieldType extends AbstractType
{
    public const NAME = 'oro_integration_webhook_field';

    public function __construct(
        private ManagerRegistry $registry
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(
            new WebhookConsumerSettingsDataTransformer($this->registry, $options['webhook_processor'])
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->define('webhook_processor')
            ->required()
            ->allowedTypes('string');
    }

    #[\Override]
    public function getParent()
    {
        return HiddenType::class;
    }

    #[\Override]
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
