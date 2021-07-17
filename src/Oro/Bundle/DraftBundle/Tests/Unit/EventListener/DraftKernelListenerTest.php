<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DraftBundle\Duplicator\Extension\DraftSourceExtension;
use Oro\Bundle\DraftBundle\Duplicator\ExtensionProvider;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\EventListener\DraftKernelListener;
use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Bundle\DraftBundle\Manager\DraftManager;
use Oro\Bundle\DraftBundle\Manager\Publisher;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerArgumentsEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class DraftKernelListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testOnKernelControllerArguments(): void
    {
        $source = new DraftableEntityStub();
        /** @var HttpKernelInterface|\PHPUnit\Framework\MockObject\MockObject $httpKernel */
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(Request::class);
        $controller = function () {
        };
        $draftManger = $this->getDraftManager();
        /** @var DraftHelper|\PHPUnit\Framework\MockObject\MockObject $draftHelper */
        $draftHelper = $this->createMock(DraftHelper::class);
        $draftHelper
            ->expects($this->once())
            ->method('isSaveAsDraftAction')
            ->willReturn(true);

        $event = new FilterControllerArgumentsEvent(
            $httpKernel,
            $controller,
            [$source, 'any argument'],
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $listener = new DraftKernelListener($draftManger, $draftHelper);
        $listener->onKernelControllerArguments($event);

        $actualArguments = $event->getArguments();
        $this->assertNotSame([$source, 'any argument'], $actualArguments);

        /** @var DraftableInterface $draftableArgument */
        $draftableArgument = reset($actualArguments);
        $this->assertSame($source, $draftableArgument->getDraftSource());
    }

    private function getDraftManager(): DraftManager
    {
        $extension = new DraftSourceExtension();

        $contextAccessor = new ContextAccessor();
        /** @var Publisher|\PHPUnit\Framework\MockObject\MockObject $publisher */
        $publisher = $this->createMock(Publisher::class);
        $extensionProvider = new ExtensionProvider(new ArrayCollection([$extension]));

        return new DraftManager($extensionProvider, $contextAccessor, $publisher);
    }
}
