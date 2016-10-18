<?php

namespace Oro\Bundle\CurrencyBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class LoadAdditionalCurrencies extends AbstractFixture implements ContainerAwareInterface
{
    const ORO_CURRENCY_ALLOWED_CURRENCIES = 'oro_currency.allowed_currencies';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.global');
        $currencies = (array)$configManager->get(self::ORO_CURRENCY_ALLOWED_CURRENCIES, []);
        $currencies = array_unique(array_merge($currencies, ['EUR']));
        $configManager->set(self::ORO_CURRENCY_ALLOWED_CURRENCIES, $currencies);
        $configManager->flush();
    }
}
