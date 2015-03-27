<?php

namespace Oro\Bundle\TrackingBundle\Migration\Extension;

interface VisitEventAssociationExtensionAwareInterface
{
    /**
     * Sets the identifier tracking visit association
     *
     * @param VisitEventAssociationExtension $extension
     */
    public function setVisitEventAssociationExtension(VisitEventAssociationExtension $extension);
}
