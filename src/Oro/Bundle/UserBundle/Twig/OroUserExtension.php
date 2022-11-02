<?php

namespace Oro\Bundle\UserBundle\Twig;

use Oro\Bundle\UserBundle\Provider\GenderProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to display a translated gender label:
 *   - oro_gender
 */
class OroUserExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return GenderProvider
     */
    protected function getGenderProvider()
    {
        return $this->container->get('oro_user.gender_provider');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_gender', [$this, 'getGenderLabel'])
        ];
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getGenderLabel($name)
    {
        if (!$name) {
            return null;
        }

        return $this->getGenderProvider()->getLabelByName($name);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_user.gender_provider' => GenderProvider::class,
        ];
    }
}
