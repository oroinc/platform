<?php

namespace Oro\Bundle\SegmentBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Segments
 *
 * @package Oro\Bundle\SegmentBundle\Tests\Selenium\Pages
 * @method Segments openSegments openSegments(string)
 * {@inheritdoc}
 */
class Segments extends AbstractPageFilteredGrid
{
    const URL = 'segment';
    protected $redirectUrl = self::URL;

    //public function __construct($testCase, $redirect = true)
    //{
    //    $this->redirectUrl = self::URL;
    //    parent::__construct($testCase, $redirect);
    //}

    public function open($entityData = array())
    {
        $contact = $this->getEntity($entityData);
        $contact->click();
        sleep(1);
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new SegmentData($this->test);
    }
}
