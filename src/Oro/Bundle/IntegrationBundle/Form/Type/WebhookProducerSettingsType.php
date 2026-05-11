<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Model\WebhookTopic;
use Oro\Bundle\IntegrationBundle\Provider\WebhookConfigurationProvider;
use Oro\Bundle\IntegrationBundle\Provider\WebhookFormatProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for WebhookProducerSettings entity.
 */
class WebhookProducerSettingsType extends AbstractType
{
    public function __construct(
        private WebhookConfigurationProvider $webhookConfigurationProvider,
        private WebhookFormatProvider $webhookFormatProvider
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $availableTopics = $this->webhookConfigurationProvider->getAvailableTopics();

        $builder
            ->add('enabled', CheckboxType::class, [
                'label' => 'oro.integration.webhookproducersettings.enabled.label',
                'required' => false
            ])
            ->add('notificationUrl', UrlType::class, [
                'label' => 'oro.integration.webhookproducersettings.notification_url.label',
                'required' => true
            ])
            ->add('secret', PasswordType::class, [
                'label' => 'oro.integration.webhookproducersettings.secret.label',
                'required' => false
            ])
            ->add(
                $builder->create(
                    'topic',
                    Select2ChoiceType::class,
                    [
                        'label' => 'oro.integration.webhookproducersettings.topic.label',
                        'required' => true,
                        'choices' => $availableTopics,
                        'choice_value' => 'name',
                        'choice_label' => function (?WebhookTopic $topic): string {
                            return $topic ? $topic->getName() . ' - ' . $topic->getLabel() : 'N/A';
                        },
                        'choice_attr' => function (?WebhookTopic $topic): array {
                            $icon = $topic->getMetadata()['icon'] ?? 'fa-podcast';

                            return [
                                'data-icon' => $icon,
                                'data-label' => $topic->getLabel()
                            ];
                        },
                        'choice_translation_domain' => false,
                        'placeholder' => 'oro.integration.webhookproducersettings.topic.placeholder',
                        'configs' => [
                            'minimumResultsForSearch' => 1,
                            'result_template_twig' => '@OroIntegration/Autocomplete/webhook/topicResult.html.twig',
                            'selection_template_twig' => '@OroIntegration/Autocomplete/webhook/topicSelection.html.twig'
                        ]
                    ]
                )->addModelTransformer(
                    new CallbackTransformer(
                        function (?string $topicName) use ($availableTopics) {
                            if (!$topicName) {
                                return null;
                            }

                            return $availableTopics[$topicName] ?? null;
                        },
                        function (?WebhookTopic $topic) {
                            return $topic?->getName();
                        }
                    )
                )
            )
            ->add('format', ChoiceType::class, [
                'label' => 'oro.integration.webhookproducersettings.format.label',
                'required' => true,
                'choice_loader' => new CallbackChoiceLoader(function () {
                    return array_flip($this->webhookFormatProvider->getFormats());
                }),
                'placeholder' => 'oro.integration.webhookproducersettings.format.placeholder'
            ])
            ->add('verifySsl', CheckboxType::class, [
                'label' => 'oro.integration.webhookproducersettings.verify_ssl.label',
                'required' => false
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WebhookProducerSettings::class
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_integration_webhook_producer_settings';
    }
}
