<?php

namespace Oro\Bundle\TestFrameworkBundle\EventListener;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Checks if session was initialized and saves if current request is master.
 * Runs on 'kernel.response' in test environment.
 *
 * @see \Symfony\Component\HttpKernel\EventListener\TestSessionListener
 */
class TestSessionListener implements EventSubscriberInterface
{
    private ContainerInterface $container;

    private array $sessionOptions;

    private string $sessionId = '';

    public function __construct(ContainerInterface $container, array $sessionOptions = [])
    {
        $this->container = $container;
        $this->sessionOptions = $sessionOptions;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $session = $this->getSession();
        if (!$session) {
            return;
        }

        if ($session->getId() === $event->getRequest()->cookies->get($session->getName())) {
            return;
        }

        if (!$event->isMasterRequest()) {
            return;
        }

        // bootstrap the session
        if (!$session = $this->getSession()) {
            return;
        }

        $cookies = $event->getRequest()->cookies;

        if ($cookies->has($session->getName())) {
            $this->sessionId = $cookies->get($session->getName());
            $session->setId($this->sessionId);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        if ($wasStarted = $session->isStarted()) {
            $session->save();
        }

        if ($session instanceof Session ? !$session->isEmpty() ||
            (null !== $this->sessionId && $session->getId() !== $this->sessionId) : $wasStarted) {
            $this->setCookieToResponse($event->getResponse(), $session);
        }
    }

    private function setCookieToResponse(Response $response, Session $session): void
    {
        $params = session_get_cookie_params() + ['samesite' => null];
        foreach ($this->sessionOptions as $k => $v) {
            if (str_starts_with($k, 'cookie_')) {
                $params[substr($k, 7)] = $v;
            }
        }

        foreach ($response->headers->getCookies() as $cookie) {
            if ($session->getName() === $cookie->getName() &&
                $params['path'] === $cookie->getPath() &&
                $params['domain'] === $cookie->getDomain()) {
                return;
            }
        }

        $response->headers->setCookie(
            new Cookie(
                $session->getName(),
                $session->getId(),
                0 === $params['lifetime'] ? 0 : time() + $params['lifetime'],
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly'],
                false,
                $params['samesite'] ?: null
            )
        );
        $this->sessionId = $session->getId();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 192],
            KernelEvents::RESPONSE => ['onKernelResponse', -128],
        ];
    }

    private function getSession(): ?SessionInterface
    {
        if (!$this->container->has('session')) {
            return null;
        }

        return $this->container->get('session');
    }
}
