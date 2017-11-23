<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Annotation;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Oro\Bundle\SecurityBundle\Annotation\AclListener;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Bundle\SecurityBundle\Metadata\ActionMetadataProvider;
use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class AclListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigMetadataDumperInterface */
    private $dumper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AclAnnotationProvider */
    private $aclAnnotationProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionMetadataProvider */
    private $actionMetadataProvider;

    /** @var AclListener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->dumper = $this->createMock(ConfigMetadataDumperInterface::class);
        $this->aclAnnotationProvider = $this->createMock(AclAnnotationProvider::class);
        $this->actionMetadataProvider = $this->createMock(ActionMetadataProvider::class);

        $container = TestContainerBuilder::create()
            ->add('oro_security.acl.annotation_provider', $this->aclAnnotationProvider)
            ->add('oro_security.action_metadata_provider', $this->actionMetadataProvider)
            ->getContainer($this);

        $this->listener = new AclListener($this->dumper, $container);
    }

    /**
     * @param bool $isMasterRequest
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|GetResponseEvent
     */
    private function getEvent($isMasterRequest = true)
    {
        $event = $this->createMock(GetResponseEvent::class);
        $event->expects(self::any())
            ->method('isMasterRequest')
            ->willReturn($isMasterRequest);

        return $event;
    }

    public function testOnKernelRequestIsNotFresh()
    {
        $this->dumper->expects(self::once())
            ->method('isFresh')
            ->willReturn(false);

        $this->aclAnnotationProvider->expects(self::once())
            ->method('warmUpCache');
        $this->actionMetadataProvider->expects(self::once())
            ->method('warmUpCache');
        $this->dumper->expects(self::once())
            ->method('dump');

        $this->listener->onKernelRequest($this->getEvent());
    }

    public function testOnKernelRequestIsFresh()
    {
        $this->dumper->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);

        $this->aclAnnotationProvider->expects(self::never())
            ->method('warmUpCache');
        $this->actionMetadataProvider->expects(self::never())
            ->method('warmUpCache');
        $this->dumper->expects(self::never())
            ->method('dump');

        $this->listener->onKernelRequest($this->getEvent());
    }

    public function testOnKernelRequestForSubRequest()
    {
        $this->dumper->expects(self::never())
            ->method('isFresh');
        $this->aclAnnotationProvider->expects(self::never())
            ->method('warmUpCache');
        $this->actionMetadataProvider->expects(self::never())
            ->method('warmUpCache');
        $this->dumper->expects(self::never())
            ->method('dump');

        $this->listener->onKernelRequest($this->getEvent(false));
    }
}
