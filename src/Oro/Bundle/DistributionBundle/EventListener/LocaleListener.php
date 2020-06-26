<?php

namespace Oro\Bundle\DistributionBundle\EventListener;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\DistributionBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContextAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Set locale by request to DistributionBundle Translator.
 */
class LocaleListener implements EventSubscriberInterface
{
    const DEFAULT_LANGUAGE = 'en';

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
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            // must be registered after authentication
            KernelEvents::REQUEST => [['onKernelRequest', 7]],
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($this->container->getParameter('translator.class') === Translator::class) {
            $this->setLocale($event->getRequest());
        }
    }

    /**
     * @param Request $request
     */
    private function setLocale(Request $request = null)
    {
        if (!$request || !$this->getIsInstalled()) {
            return;
        }

        $language = $this->getLanguageFromConfig();
        $language = $language ?: static::DEFAULT_LANGUAGE;

        if (!$request->attributes->get('_locale')) {
            $request->setLocale($language);
            if (null !== $this->getRouter()) {
                $this->getRouter()->getContext()->setParameter('_locale', $language);
            }
        }

        $this->getTranslator()->setLocale($language);
    }

    /**
     * @return bool
     */
    private function getIsInstalled()
    {
        return $this->container->getParameter('installed');
    }

    /**
     * @return string|null
     */
    private function getLanguageFromConfig()
    {
        return $this->container->get('doctrine')
            ->getConnection()
            ->fetchColumn(
                'SELECT text_value FROM oro_config_value WHERE name = :name AND section = :section',
                ['name' => 'language', 'section' => 'oro_locale'],
                0,
                ['name' => Types::STRING, 'section' => Types::STRING]
            );
    }

    /**
     * @return RequestContextAwareInterface|null
     */
    private function getRouter()
    {
        return $this->container->get('router', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    }

    /**
     * @return TranslatorInterface
     */
    private function getTranslator()
    {
        return $this->container->get('translator');
    }
}
