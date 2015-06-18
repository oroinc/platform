<?php

namespace Oro\Bundle\SegmentBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\SegmentBundle\Tests\Selenium\Pages\Segments;

class SegmentsTest extends Selenium2TestCase
{
     /**
     * @return string
     */
    public function testCreateSegment()
    {
        $segmentName = 'Segment_' . mt_rand();
        $login = $this->login();
        /** @var Segments $login */
        $login->openSegments('Oro\Bundle\SegmentBundle')
            ->assertTitle('All - Manage Segments - Reports & Segments')
            ->add()
            ->setName($segmentName)
            ->setEntity('User')
            ->setType('Dynamic')
            ->setOrganization('Main')
            ->addColumn(['First name', 'Last name', 'Username'])
            ->addFieldCondition('Username', 'Admin')
            ->save();
    }
}
