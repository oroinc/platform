<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;

class BeforeMapObjectSearchListener
{
    const EMAIL_CLASS_NAME = 'Oro\Bundle\EmailBundle\Entity\Email';
    const EMAIL_USER_CLASS_NAME = 'Oro\Bundle\EmailBundle\Entity\EmailUser';

    /**
     * @param SearchMappingCollectEvent $event
     */
    public function addEntityMapTitleFieldEvent(SearchMappingCollectEvent $event)
    {
        $mapConfig     = $event->getMappingConfig();
        //sets title fields for activity context name, alias set to empty for avoiding warnings and notices
        $mapConfig[self::EMAIL_CLASS_NAME] = $mapConfig[self::EMAIL_USER_CLASS_NAME];
        $mapConfig[self::EMAIL_CLASS_NAME]['title_fields'] = ['subject'];
        $event->setMappingConfig($mapConfig);
    }
}
