<?php

namespace Oro\Bundle\TrackingBundle\Migration\Extension;


interface IdentifierEventExtensionAwareInterface
{
    /**
     * Sets the
     *
     * @param IdentifierEventExtension $extension
     */
    public function setIdentifierEventExtension(IdentifierEventExtension $extension);
}
