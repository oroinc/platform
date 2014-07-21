<?php

namespace Oro\Bundle\ImportExportBundle\TemplateFixture;

interface TemplateFixtureInterface extends TemplateEntityRepositoryInterface
{
    /**
     * Get fixtures for template data.
     *
     * @return \Iterator
     */
    public function getData();
}
