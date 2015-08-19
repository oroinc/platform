<?php

namespace Oro\Bundle\SegmentBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Segments
 *
 * @package Oro\Bundle\SegmentBundle\Tests\Selenium\Pages
 * @method Segments openSegments(string $bundlePath)
 * @method Segment add()
 * {@inheritdoc}
 */
class Segments extends AbstractPageFilteredGrid
{
    const URL = 'segment';
    const NEW_ENTITY_BUTTON = "//a[@title='Create Segment']";

    public function entityView()
    {
        return new SegmentData($this->test);
    }

    public function entityNew()
    {
        return new Segment($this->test);
    }
}
