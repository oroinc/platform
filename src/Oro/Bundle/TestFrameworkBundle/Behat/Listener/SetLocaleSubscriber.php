<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Testwork\EventDispatcher\Event\BeforeExerciseCompleted;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer\KernelServiceFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SetLocaleSubscriber implements EventSubscriberInterface
{
    const DEFAULT_TIMEZONE = 'America/Los Angeles';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var KernelServiceFactory
     */
    protected $kernelServiceFactory;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param KernelServiceFactory $kernelServiceFactory
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }
    
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // must be after FeatureIsolationSubscriber::beforeFeature
            BeforeExerciseCompleted::BEFORE  => 'setLocale',
        ];
    }

    public function setLocale()
    {
        $this->configManager->set('oro_locale.timezone', 'America/Los_Angeles');
        $this->configManager->flush();
    }
}
