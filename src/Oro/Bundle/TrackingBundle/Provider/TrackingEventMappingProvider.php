<?php

namespace Oro\Bundle\TrackingBundle\Provider;


class TrackingEventMappingProvider
{
    /**
     * Get array with identification entities
     *
     * @return array
     */
    public function getIdentifierEntities()
    {
        return [
            'OroCRM\Bundle\MagentoBundle\Entity\Customer'
        ];
    }

    public function getIdentifierByTrackingWebsite()
    {
    }

    public function getIdentifierMapping() //getIdentifierMapper
    {
    }
}
