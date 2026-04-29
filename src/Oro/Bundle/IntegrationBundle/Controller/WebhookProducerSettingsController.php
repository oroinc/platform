<?php

namespace Oro\Bundle\IntegrationBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Form\Type\WebhookProducerSettingsType;
use Oro\Bundle\IntegrationBundle\Provider\WebhookConfigurationProvider;
use Oro\Bundle\IntegrationBundle\Provider\WebhookFormatProvider;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD controller for WebhookProducerSettings entity.
 */
#[Route(path: '/webhook-producer-settings')]
class WebhookProducerSettingsController extends AbstractController
{
    #[Route(
        path: '/{_format}',
        name: 'oro_integration_webhook_producer_settings_index',
        requirements: ['_format' => 'html|json'],
        defaults: ['_format' => 'html']
    )]
    #[Template('@OroIntegration/WebhookProducerSettings/index.html.twig')]
    #[AclAncestor('oro_integration_webhook_producer_settings_view')]
    public function indexAction(): array
    {
        return [
            'entity_class' => WebhookProducerSettings::class
        ];
    }

    #[Route(
        path: '/view/{id}',
        name: 'oro_integration_webhook_producer_settings_view',
        requirements: ['id' => '[0-9a-f\-]+']
    )]
    #[Template('@OroIntegration/WebhookProducerSettings/view.html.twig')]
    #[AclAncestor('oro_integration_webhook_producer_settings_view')]
    public function viewAction(WebhookProducerSettings $entity): array
    {
        return [
            'entity' => $entity,
            'webhook_formats' => $this->container->get(WebhookFormatProvider::class)->getFormats(),
            'webhook_topics' => $this->container->get(WebhookConfigurationProvider::class)->getAvailableTopics()
        ];
    }

    #[Route(path: '/create', name: 'oro_integration_webhook_producer_settings_create')]
    #[Template('@OroIntegration/WebhookProducerSettings/update.html.twig')]
    #[AclAncestor('oro_integration_webhook_producer_settings_create')]
    public function createAction(Request $request): array|Response
    {
        return $this->update(new WebhookProducerSettings(), $request);
    }

    #[Route(
        path: '/update/{id}',
        name: 'oro_integration_webhook_producer_settings_update',
        requirements: ['id' => '[0-9a-f\-]+']
    )]
    #[Template('@OroIntegration/WebhookProducerSettings/update.html.twig')]
    #[AclAncestor('oro_integration_webhook_producer_settings_update')]
    public function updateAction(WebhookProducerSettings $entity, Request $request): array|Response
    {
        return $this->update($entity, $request);
    }

    protected function update(WebhookProducerSettings $entity, Request $request): array|Response
    {
        return $this->container->get(UpdateHandlerFacade::class)->update(
            $entity,
            $this->createForm(WebhookProducerSettingsType::class, $entity),
            $this->container->get(TranslatorInterface::class)->trans(
                'oro.integration.controller.webhook_producer_settings.saved.message'
            ),
            $request,
            null
        );
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                UpdateHandlerFacade::class,
                TranslatorInterface::class,
                WebhookFormatProvider::class,
                WebhookConfigurationProvider::class
            ]
        );
    }
}
