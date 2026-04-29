<?php

namespace Oro\Bundle\IntegrationBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\WebhookConsumerSettings;
use Oro\Bundle\IntegrationBundle\Processor\ProcessorRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Entry point for webhook processing.
 */
#[Route(path: '/webhook')]
class WebhookController extends AbstractController
{
    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            ManagerRegistry::class,
            ProcessorRegistry::class,
            LoggerInterface::class
        ];
    }

    #[Route('/consume/{id}', name: 'oro_integration_webhook_consume')]
    public function consume(string $id, Request $request): Response
    {
        try {
            // Check UUID v4 format
            if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $id)) {
                return new Response(null, Response::HTTP_NOT_FOUND);
            }

            $repo = $this->container->get(ManagerRegistry::class)->getRepository(WebhookConsumerSettings::class);
            $webhook = $repo->find($id);

            if (!$webhook || !$webhook->isEnabled()) {
                return new Response(null, Response::HTTP_NOT_FOUND);
            }

            $response = $this->container->get(ProcessorRegistry::class)
                ->getProcessor($webhook->getProcessor())
                ->process($webhook, $request);

            if (!$response instanceof Response) {
                $response = new Response('OK', Response::HTTP_OK);
            }

            return $response;
        } catch (\Exception $e) {
            $this->container->get(LoggerInterface::class)->error(
                'Failed to process webhook notification',
                ['exception' => $e]
            );

            return new Response(null, Response::HTTP_NOT_FOUND);
        }
    }
}
