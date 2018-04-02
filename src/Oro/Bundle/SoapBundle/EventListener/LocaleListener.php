<?php

namespace Oro\Bundle\SoapBundle\EventListener;

use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleListener implements EventSubscriberInterface
{
    const API_PREFIX = '/api/rest/';

    /** @var TranslatableListener */
    private $translatableListener = false;

    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $locale = str_replace('-', '_', $request->query->get('locale'));
        if ($locale && $this->isApiRequest($request)) {
            $request->setLocale($locale);
            $this->getTranslatableListener()->setTranslatableLocale($locale);
        }
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    protected function isApiRequest(Request $request)
    {
        return strpos($request->getPathInfo(), self::API_PREFIX) === 0;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            // must be registered after Symfony's original LocaleListener
            KernelEvents::REQUEST  => array(array('onKernelRequest', -17)),
        );
    }

    /**
     * @return TranslatableListener
     */
    protected function getTranslatableListener()
    {
        if ($this->translatableListener === false) {
            $this->translatableListener = $this->container->get('stof_doctrine_extensions.listener.translatable');
        }

        return $this->translatableListener;
    }
}
