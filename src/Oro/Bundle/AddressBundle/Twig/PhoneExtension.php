<?php

namespace Oro\Bundle\AddressBundle\Twig;

use Oro\Bundle\AddressBundle\Provider\PhoneProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to extract phone number data from an entity:
 *   - phone_number
 *   - phone_numbers
 */
class PhoneExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return PhoneProviderInterface
     */
    protected function getPhoneProvider()
    {
        return $this->container->get('oro_address.provider.phone');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('phone_number', [$this, 'getPhoneNumber']),
            new TwigFunction('phone_numbers', [$this, 'getPhoneNumbers']),
        ];
    }

    /**
     * @param object|null $object
     * @return string|null
     */
    public function getPhoneNumber($object)
    {
        if (!$object) {
            return null;
        }

        return $this->getPhoneProvider()->getPhoneNumber($object);
    }

    /**
     * @param object|null $object
     * @return array [['phone' => <string>, 'object' => <object>], ...]
     */
    public function getPhoneNumbers($object)
    {
        if (!$object) {
            return [];
        }

        $result = [];
        $phones = $this->getPhoneProvider()->getPhoneNumbers($object);
        foreach ($phones as $row) {
            $result[] = ['phone' => $row[0], 'object' => $row[1]];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_address.provider.phone' => PhoneProviderInterface::class,
        ];
    }
}
