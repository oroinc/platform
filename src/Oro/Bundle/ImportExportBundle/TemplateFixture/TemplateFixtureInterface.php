<?php

namespace Oro\Bundle\ImportExportBundle\TemplateFixture;

interface TemplateFixtureInterface
{
    /**
     * Get fixtures for template data.
     *
     * @return \Iterator
     */
    public function getData();
}
