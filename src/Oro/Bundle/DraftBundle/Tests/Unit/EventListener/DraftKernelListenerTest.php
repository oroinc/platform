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
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class DraftKernelListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testOnKernelControllerArguments(): void
    {
        $source = new DraftableEntityStub();
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);
        $controller = static fn () => null;
        $draftManger = $this->getDraftManager();
        $draftHelper = $this->createMock(DraftHelper::class);
        $draftHelper
            ->expects(self::once())
            ->method('isSaveAsDraftAction')
            ->willReturn(true);

        $event = new ControllerArgumentsEvent(
            $httpKernel,
            $controller,
            [$source, 'any argument'],
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $listener = new DraftKernelListener($draftManger, $draftHelper);
        $listener->onKernelControllerArguments($event);

        $actualArguments = $event->getArguments();
        self::assertNotSame([$source, 'any argument'], $actualArguments);

        /** @var DraftableInterface $draftableArgument */
        $draftableArgument = reset($actualArguments);
        self::assertSame($source, $draftableArgument->getDraftSource());
    }

    private function getDraftManager(): DraftManager
    {
        $extension = new DraftSourceExtension();

        $contextAccessor = new ContextAccessor();
        $publisher = $this->createMock(Publisher::class);
        $extensionProvider = new ExtensionProvider(new ArrayCollection([$extension]));

        return new DraftManager($extensionProvider, $contextAccessor, $publisher);
    }
}
