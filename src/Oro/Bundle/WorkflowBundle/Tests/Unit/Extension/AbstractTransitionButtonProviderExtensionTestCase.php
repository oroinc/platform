<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\ActionBundle\Provider\OriginalUrlProvider;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Oro\Bundle\WorkflowBundle\Extension\AbstractButtonProviderExtension;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;

abstract class AbstractTransitionButtonProviderExtensionTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowRegistry;

    /** @var RouteProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $routeProvider;

    /** @var OriginalUrlProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $originalUrlProvider;

    /** @var CurrentApplicationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $applicationProvider;

    /** @var TransitionOptionsResolver|\PHPUnit\Framework\MockObject\MockObject */
    protected $optionsResolver;

    /** @var AbstractButtonProviderExtension */
    protected $extension;

    protected function setUp(): void
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $this->routeProvider = $this->createMock(RouteProviderInterface::class);
        $this->originalUrlProvider = $this->createMock(OriginalUrlProvider::class);
        $this->applicationProvider = $this->createMock(CurrentApplicationProviderInterface::class);
        $this->optionsResolver = $this->createMock(TransitionOptionsResolver::class);

        $this->extension = $this->createExtension();
        $this->extension->setApplicationProvider($this->applicationProvider);
    }

    /**
     * @return string
     */
    abstract protected function getApplication();

    /**
     * @return AbstractButtonProviderExtension
     */
    abstract protected function createExtension();

    protected function getButtonContext(string $entityClass): ButtonContext
    {
        $context = new ButtonContext();
        $context->setEntity($entityClass)
            ->setEnabled(true)
            ->setUnavailableHidden(false);

        return $context;
    }

    protected function getTransitionManager(array $transitions, string $method): TransitionManager
    {
        $manager = $this->createMock(TransitionManager::class);
        $manager->expects($this->any())
            ->method($method)
            ->willReturn(new ArrayCollection($transitions));

        return $manager;
    }

    protected function getTransition(string $name): Transition
    {
        $transition = new Transition($this->optionsResolver);

        return $transition->setName($name);
    }
}
