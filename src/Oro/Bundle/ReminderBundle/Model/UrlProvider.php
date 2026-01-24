<?php

namespace Oro\Bundle\ReminderBundle\Model;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Symfony\Component\Routing\RouterInterface;

/**
 * Generates URLs for reminder-related entities.
 *
 * This provider uses entity configuration metadata to determine the appropriate
 * route for a reminder's related entity. It generates URLs that can be used in
 * reminder notifications to direct users to the relevant entity page. The provider
 * supports both view routes (with entity ID) and named routes, falling back to an
 * empty string if no suitable route is found in the entity configuration.
 */
class UrlProvider
{
    /**
     * @var ConfigManager
     */
    protected $manager;

    /**
     * @var RouterInterface
     */
    protected $router;

    public function __construct(ConfigManager $manager, RouterInterface $router)
    {
        $this->manager = $manager;
        $this->router  = $router;
    }

    /**
     * Generate rout for entity (using entity config)
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
