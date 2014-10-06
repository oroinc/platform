<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Event;

use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Event\ImportStrategyListener;
use Oro\Bundle\UserBundle\Entity\User;

class ImportStrategyListenerTest extends \PHPUnit_Framework_TestCase
{
    const CURRENT_ORGANIZATION = 'current';
    const STORED_ORGANIZATION  = 'stored';

    /**
     * @param object $entity
     * @param string|null $expectedOrganization
     * @dataProvider onProcessAfterDataProvider
     */
    public function testOnProcessAfter($entity, $expectedOrganization)
    {
        $strategy = $this->getMock('Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface');

        $currentOrganization = new Organization();
        $currentOrganization->setName(self::CURRENT_ORGANIZATION);

        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $securityFacade->expects($this->any())
            ->method('getOrganization')
            ->will($this->returnValue($currentOrganization));

        $listener = new ImportStrategyListener($securityFacade);

        $event = new StrategyEvent($strategy, $entity);
        $listener->onProcessAfter($event);
        if ($expectedOrganization) {
            $this->assertEquals($expectedOrganization, $event->getEntity()->getOrganization());
        }
    }

    /**
     * @return array
     */
    public function onProcessAfterDataProvider()
    {
        $storedOrganization = new Organization();
        $storedOrganization->setName(self::STORED_ORGANIZATION);

        $storedEntity = new User();
        $storedEntity->setOrganization($storedOrganization);

        return [
            'not supported' => [
                'entity' => new \stdClass(),
                'expectedOrganization' => null,
            ],
            'stored organization' => [
                'entity' => $storedEntity,
                'expectedOrganization' => self::STORED_ORGANIZATION,
            ],
            'empty organization' => [
                'entity' => new User(),
                'expectedOrganization' => self::CURRENT_ORGANIZATION,
            ]
        ];
    }
}
