<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Entity;

use Oro\Bundle\TrackingBundle\Entity\TrackingWebsite;
use Oro\Bundle\UserBundle\Entity\User;

class TrackingWebsiteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TrackingWebsite
     */
    protected $website;

    protected function setUp()
    {
        $this->website = new TrackingWebsite();
    }

    public function testId()
    {
        $this->assertNull($this->website->getId());
    }

    public function testPrePersist()
    {
        $this->assertNull($this->website->getCreatedAt());
        $this->website->prePersist();
        $this->assertInstanceOf('\DateTime', $this->website->getCreatedAt());
    }

    public function testPreUpdate()
    {
        $this->assertNull($this->website->getUpdatedAt());
        $this->website->preUpdate();
        $this->assertInstanceOf('\DateTime', $this->website->getUpdatedAt());
    }

    /**
     * @param string $property
     * @param mixed  $value
     * @param mixed  $expected
     *
     * @dataProvider propertyProvider
     */
    public function testProperties($property, $value, $expected)
    {
        $this->assertNull(
            $this->website->{'get' . $property}()
        );

        $this->website->{'set' . $property}($value);

        $this->assertEquals(
            $expected,
            $this->website->{'get' . $property}()
        );
    }

    /**
     * @return array
     */
    public function propertyProvider()
    {
        $date = new \DateTime();
        $user = new User();

        return [
            ['name', 'test', 'test'],
            ['identifier', 'uniqid', 'uniqid'],
            ['url', 'http://example.com', 'http://example.com'],
            ['createdAt', $date, $date],
            ['updatedAt', $date, $date],
            ['owner', $user, $user],
        ];
    }
}
