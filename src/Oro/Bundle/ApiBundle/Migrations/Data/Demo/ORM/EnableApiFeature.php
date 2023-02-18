<?php

namespace Oro\Bundle\ApiBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Fixture that enables API feature.
 */
class EnableApiFeature extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $configManager = $this->container->get('oro_config.global');
        $configManager->set('oro_api.web_api', true);
        $configManager->flush();
    }
}
