<?php

namespace Oro\Bundle\ImportExportBundle\TemplateFixture;

interface TemplateManagerAwareInterface
{
    /**
     * @param TemplateManager $templateManager
     */
    public function setTemplateManager(TemplateManager $templateManager);
}
