<?php

namespace Oro\Bundle\SegmentBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\SegmentBundle\Tests\Selenium\Pages\Segments;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;

class SegmentsTest extends Selenium2TestCase
{
     /**
     * Test to check that Segment can be created and it returns correct data
     * @return string
     */
    public function testCreateSegment()
    {
        $segmentName = 'Segment_' . mt_rand();
        $userName = 'admin';

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
            ->addFilterCondition('Field condition', 'Username', $userName)
            ->save();
        /** @var Segments $login */
        $login->openSegments('Oro\Bundle\SegmentBundle')
            ->filterBy('Name', $segmentName)
            ->open(array($segmentName))
            ->filterBy('Username', $userName)
            ->entityExists(array($userName));

        return $segmentName;
    }

    /**
     * Test to check deletion of existing segment
     * @depends testCreateSegment
     * @param $segmentName
     */
    public function testDeleteSegment($segmentName)
    {
        $login = $this->login();
        /** @var Segments $login */
        $login->openSegments('Oro\Bundle\SegmentBundle')
            ->filterBy('Name', $segmentName)
            ->delete($segmentName)
            ->filterBy('Name', $segmentName)
            ->assertNoDataMessage('No segment was found to match your search');
    }

    /**
     * Test to check that manual segment update data after click Refresh Segment button
     */
    public function testManualSegmentUpdate()
    {
        $segmentName = 'Segment_' . mt_rand();
        $userName = 'user_' . mt_rand();

        $login = $this->login();
        /** @var Segments $login */
        $login->openSegments('Oro\Bundle\SegmentBundle')
            ->assertTitle('All - Manage Segments - Reports & Segments')
            ->add()
            ->setName($segmentName)
            ->setEntity('User')
            ->setType('Manual')
            ->setOrganization('Main')
            ->addColumn(['First name', 'Last name', 'Username'])
            ->addFilterCondition('Field condition', 'Username', $userName)
            ->save();
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->assertTitle('All - Users - User Management - System')
            ->add()
            ->assertTitle('Create User - Users - User Management - System')
            ->setUsername($userName)
            ->enable()
            ->setOwner('Main')
            ->setFirstPassword('123123q')
            ->setSecondPassword('123123q')
            ->setFirstName('First_'.$userName)
            ->setLastName('Last_'.$userName)
            ->setEmail($userName.'@mail.com')
            ->setRoles(array('Manager', 'Marketing Manager'), true)
            ->setOrganization('OroCRM')
            ->uncheckInviteUser()
            ->save()
            ->assertMessage('User saved');
        /** @var Segments $login */
        $login->openSegments('Oro\Bundle\SegmentBundle')
            ->filterBy('Name', $segmentName)
            ->open(array($segmentName))
            ->assertNoDataMessage('No records found')
            ->refreshSegment()
            ->filterBy('Username', $userName)
            ->entityExists(array($userName));
    }

    /**
     * Test to check that dynamic segment update data correctly
     */
    public function testDynamicSegmentUpdate()
    {
        $segmentName = 'Segment_' . mt_rand();
        $userName = 'user_' . mt_rand();

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
            ->addFilterCondition('Field condition', 'Username', $userName)
            ->save();
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->assertTitle('All - Users - User Management - System')
            ->add()
            ->assertTitle('Create User - Users - User Management - System')
            ->setUsername($userName)
            ->enable()
            ->setOwner('Main')
            ->setFirstPassword('123123q')
            ->setSecondPassword('123123q')
            ->setFirstName('First_'.$userName)
            ->setLastName('Last_'.$userName)
            ->setEmail($userName.'@mail.com')
            ->setRoles(array('Manager', 'Marketing Manager'), true)
            ->setOrganization('OroCRM')
            ->uncheckInviteUser()
            ->save()
            ->assertMessage('User saved');
        /** @var Segments $login */
        $login->openSegments('Oro\Bundle\SegmentBundle')
            ->filterBy('Name', $segmentName)
            ->open(array($segmentName))
            ->filterBy('Username', $userName)
            ->entityExists(array($userName));
    }
}
