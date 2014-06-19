<?php

namespace Oro\Bundle\ActivityBundle\Provider;

interface ActivityWidgetProviderInterface
{
    /**
     * @param object $entity
     * @return bool
     */
    public function supports($entity);

    /**
     * @param object $entity
     * @return array
     */
    public function getWidgets($entity);
}
