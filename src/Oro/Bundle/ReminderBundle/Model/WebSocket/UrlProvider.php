<?php

namespace Oro\Bundle\ReminderBundle\Model\WebSocket;

use Symfony\Component\Routing\Router;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\ReminderBundle\Entity\Reminder;

class UrlProvider
{
    /**
     * @var ConfigManager
     */
    protected $manager;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @param ConfigManager $manager
     * @param Router        $router
     */
    public function __construct(ConfigManager $manager, Router $router)
    {
        $this->manager = $manager;
        $this->router = $router;
    }

    /**
     * Generate rout for entity (use entity config)
     *
     * @param Reminder $reminder
     * @return string
     */
    public function getUrl(Reminder $reminder)
    {
        $metadata = $this->manager->getEntityMetadata($reminder->getRelatedEntityClassName());

        if (isset($metadata)) {
            if (!empty($metadata->routeView)) {
                return $this->router->generate($metadata->routeView, array('id' => $reminder->getRelatedEntityId()));
            }
            if (!empty($metadata->routeName)) {
                return $this->router->generate($metadata->routeName);
            }
        }

        return '';
    }
}
