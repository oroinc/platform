<?php

namespace Oro\Bundle\ApiBundle\Security\EventListener;

use Oro\Bundle\ApiBundle\ApiDoc\RestDocUrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Redirects to the current API view after a user is logged out.
 */
class LogoutListener implements EventSubscriberInterface
{
    private HttpUtils $httpUtils;

    private RestDocUrlGeneratorInterface $restDocUrlGenerator;

    public function __construct(HttpUtils $httpUtils, RestDocUrlGeneratorInterface $restDocUrlGenerator)
    {
        $this->httpUtils = $httpUtils;
        $this->restDocUrlGenerator = $restDocUrlGenerator;
    }

    public function onLogout(LogoutEvent $event): void
    {
        if (null !== $event->getResponse()) {
            return;
        }

        $view = $event->getRequest()->query->get('_api_view');
        if ($view) {
            $event->setResponse(
                $this->httpUtils->createRedirectResponse(
                    $event->getRequest(),
                    $this->restDocUrlGenerator->generate($view)
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => ['onLogout', 128],
        ];
    }
}
