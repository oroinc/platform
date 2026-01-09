<?php

namespace Oro\Bundle\ImportExportBundle\TemplateFixture;

/**
 * Defines the contract for objects that can be made aware of the template manager.
 *
 * Classes implementing this interface can receive a reference to the template manager,
 * allowing them to access template fixtures and entity repositories during initialization
 * or processing.
 */
interface TemplateManagerAwareInterface
{
    public function setTemplateManager(TemplateManager $templateManager);
}
