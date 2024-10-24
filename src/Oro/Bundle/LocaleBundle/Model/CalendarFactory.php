<?php

namespace Oro\Bundle\LocaleBundle\Model;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Calendar factory.
 */
class CalendarFactory implements CalendarFactoryInterface, ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    #[\Override]
    public function getCalendar($locale = null, $language = null)
    {
        /** @var Calendar $result */
        $result = $this->container->get('oro_locale.calendar');
        $result->setLocale($locale);
        $result->setLanguage($language);
        return $result;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_locale.calendar' => Calendar::class
        ];
    }
}
