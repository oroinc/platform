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

    /** @var AbstractButtonProviderExtension */
    protected $extension;

    /** @var OriginalUrlProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $originalUrlProvider;

    /** @var  CurrentApplicationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $applicationProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TransitionOptionsResolver */
    protected $optionsResolver;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
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
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->workflowRegistry, $this->routeProvider, $this->extension, $this->applicationProvider);
    }

    /**
     * @return string
     */
    abstract protected function getApplication();

    /**
     * @return AbstractButtonProviderExtension
     */
    abstract protected function createExtension();

    /**
     * @param string $entityClass
     *
     * @return ButtonContext
     */
    protected function getButtonContext($entityClass)
    {
        $context = new ButtonContext();
        $context->setEntity($entityClass)
            ->setEnabled(true)
            ->setUnavailableHidden(false);

        return $context;
    }

    /**
     * @param array $transitions
     * @param string $method
     *
     * @return TransitionManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getTransitionManager(array $transitions, $method)
    {
        $manager = $this->createMock(TransitionManager::class);
        $manager->expects($this->any())
            ->method($method)
            ->willReturn(new ArrayCollection($transitions));

        return $manager;
    }

    /**
     * @param string $name
     *
     * @return Transition
     */
    protected function getTransition($name)
    {
        $transition = new Transition($this->optionsResolver);

        return $transition->setName($name);
    }
}
