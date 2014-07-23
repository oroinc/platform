<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\IntegrationBundle\Exception\LogicException;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class ConnectorContextMediator
{
    /** @var TypesRegistry */
    protected $registry;

    /** @var RegistryInterface */
    protected $doctrineRegistry;

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
            $this->getUow()->markReadOnly($transport);
        }

        return clone $this->registryLink->getService()
            ->getTransportTypeBySettingEntity($transport, $source->getType());
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
