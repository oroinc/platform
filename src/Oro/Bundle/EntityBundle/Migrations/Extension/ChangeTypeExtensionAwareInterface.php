<?php

namespace Oro\Bundle\EntityBundle\Migrations\Extension;

interface ChangeTypeExtensionAwareInterface
{
    /**
     * @param ChangeTypeExtension $changeTypeExtension
     *
     * @return void
     */
    public function setChangeTypeExtension(ChangeTypeExtension $changeTypeExtension);
}
