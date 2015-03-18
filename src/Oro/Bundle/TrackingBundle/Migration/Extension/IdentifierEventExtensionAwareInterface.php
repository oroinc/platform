<?php

namespace Oro\Bundle\TrackingBundle\Migration\Extension;

interface IdentifierEventExtensionAwareInterface
{
    /**
     * Sets the identifier tracking visit association
     *
     * @param IdentifierEventExtension $extension
     */
    public function setIdentifierEventExtension(IdentifierEventExtension $extension);
}
