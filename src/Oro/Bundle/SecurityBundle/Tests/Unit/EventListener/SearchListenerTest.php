<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Oro\Bundle\SearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SecurityBundle\EventListener\SearchListener;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsArticle;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsOrganization;

class SearchListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SearchListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $metadataProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    public function setUp()
    {
        $this->metadataProvider = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new SearchListener($this->metadataProvider, $this->securityFacade);
    }

    public function testPrepareEntityMapEvent()
    {
        $entity = new CmsArticle();
        $organization = new CmsOrganization();
        $organization->id = 3;
        $entity->setOrganization($organization);
        $data = [
            'integer' => [
                'organization' => null
            ]
        ];

        $metadata = new OwnershipMetadata('ORGANIZATION', 'organization', 'organization_id', 'organization', '');
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->will($this->returnValue($metadata));

        $event = new PrepareEntityMapEvent($entity, get_class($entity), $data, ['alias' => 'test']);
        $this->listener->prepareEntityMapEvent($event);
        $resultData = $event->getData();

        $this->assertEquals(3, $resultData['integer']['organization']);
    }
}
