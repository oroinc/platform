<?php

namespace Oro\Bundle\ImportExportBundle\TemplateFixture;

/**
 * Defines the contract for template fixtures that provide sample entity data.
 *
 * This interface extends {@see TemplateEntityRepositoryInterface} and adds a method to retrieve
 * an iterator of fixture entities. Implementations provide sample data representing
 * the structure and format of entities, used for generating export templates and
 * validating import data.
 */
interface TemplateFixtureInterface extends TemplateEntityRepositoryInterface
{
    /**
     * Get fixtures for template data.
     *
     * @return \Iterator
     */
    public function getData();
}
