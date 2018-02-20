<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Exception\LogicException;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ConnectorContextMediator
{
    /** @var TypesRegistry */
    protected $registry;

    /** @var ServiceLink */
    protected $registryLink;

    /** @var RegistryInterface */
    protected $doctrineRegistry;

    /** @var TransportInterface[] */
    protected $transportByIntegration = [];

    /**
     * @param ServiceLink       $registryLink
     * @param RegistryInterface $doctrineRegistry
     */
    public function __construct(ServiceLink $registryLink, RegistryInterface $doctrineRegistry)
    {
        $this->registryLink     = $registryLink;
        $this->doctrineRegistry = $doctrineRegistry;
    }

    /**
     * Get prepared transport
     *
     * @param ContextInterface|Integration $source
     * @param boolean                      $markReadOnly
     *
     * @throws LogicException
     * @return TransportInterface
     */
    public function getTransport($source, $markReadOnly = false)
    {
        if ($source instanceof ContextInterface) {
            $source = $this->getChannel($source);
        } elseif (!$source instanceof Integration) {
            throw new LogicException('Expected type ContextInterface or Channel');
        }

        $transport = $source->getTransport();
        if ($markReadOnly) {
            $uow = $this->getUow();
            if ($uow->getEntityState($transport, UnitOfWork::STATE_DETACHED) === UnitOfWork::STATE_MANAGED) {
                $uow->markReadOnly($transport);
            }
        }

        return clone $this->registryLink->getService()
            ->getTransportTypeBySettingEntity($transport, $source->getType());
    }

    /**
     * @param Integration $integration
     * @param bool $markReadOnly
     *
     * @return TransportInterface
     */
    public function getInitializedTransport(Integration $integration, $markReadOnly = false)
    {
        if (array_key_exists($integration->getId(), $this->transportByIntegration)) {
            return $this->transportByIntegration[$integration->getId()];
        }

        $transport = $this->getTransport($integration, $markReadOnly);
        $transport->init($integration->getTransport());

        $this->transportByIntegration[$integration->getId()] = $transport;

        return $transport;
    }

    /**
     * Reset cached transports
     */
    public function resetInitializedTransport()
    {
        $this->transportByIntegration = [];
    }

    public function onClear()
    {
        $this->resetInitializedTransport();
    }

    /**
     * Get channel instance
     *
     * @param ContextInterface $context
     *
     * @return Integration
     */
    public function getChannel(ContextInterface $context)
    {
        $channel = $this->getEm()
            ->getRepository('OroIntegrationBundle:Channel')
            ->getOrLoadById($context->getOption('channel'));

        return $channel;
    }

    /**
     * @return UnitOfWork
     */
    private function getUow()
    {
        return $this->getEm()->getUnitOfWork();
    }

    /**
     * @return EntityManager
     */
    private function getEm()
    {
        return $this->doctrineRegistry->getManager();
    }
}
