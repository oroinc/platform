<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\EventListener\PrepareResultItemListener;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PrepareResultItemListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var ObjectMapper|\PHPUnit\Framework\MockObject\MockObject */
    private $mapper;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var PrepareResultItemEvent|\PHPUnit\Framework\MockObject\MockObject */
    private $event;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $item;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $entity;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var PrepareResultItemListener */
    private $listener;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->mapper = $this->createMock(ObjectMapper::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->item = $this->createMock(Item::class);
        $this->event = $this->createMock(PrepareResultItemEvent::class);
        $this->entity = $this->createMock(User::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->listener = new PrepareResultItemListener(
            $this->urlGenerator,
            $this->mapper,
            $doctrine,
            $this->configManager,
            $this->translator
        );
    }

    /**
     * Check that process data doesn't execute any query if url and title already set
     */
    public function testProcessSetData()
    {
        $this->event->expects($this->once())
            ->method('getEntity');

        $this->event->expects($this->once())
            ->method('getResultItem')
            ->willReturn($this->item);

        $this->item->expects($this->once())
            ->method('getRecordUrl')
            ->willReturn('url');

        $config = new Config(new EntityConfigId('entity', User::class));
        $config->set('label', 'testLabel');

        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->willReturn($config);

        $this->em->expects($this->never())
            ->method('find');

        $this->listener->process($this->event);
    }

    /**
     * Generates url from existed entity
     */
    public function testProcessUrlFromEntity()
    {
        $this->event->expects($this->once())
            ->method('getEntity')
            ->willReturn($this->entity);

        $this->event->expects($this->once())
            ->method('getResultItem')
            ->willReturn($this->item);

        $this->item->expects($this->once())
            ->method('getRecordUrl')
            ->willReturn(null);

        $this->item->expects($this->exactly(2))
            ->method('getEntityName')
            ->willReturn(get_class($this->entity));

        $metadataMock = $this->createMock(ClassMetadata::class);

        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($this->entity))
            ->willReturn($metadataMock);

        $this->mapper->expects($this->exactly(2))
            ->method('getEntityMapParameter')
            ->with(get_class($this->entity), 'route')
            ->willReturn(['parameters' => ['parameter' => 'field'], 'name' => 'test_route']);

        $this->mapper->expects($this->once())
            ->method('getFieldValue')
            ->with($this->entity, 'field')
            ->willReturn('test_data');

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('test_route', ['parameter' => 'test_data'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('test_url');

        $config = new Config(new EntityConfigId('entity', User::class));
        $config->set('label', 'testLabel');

        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->willReturn($config);

        $this->listener->process($this->event);
    }

    /**
     * Process entity without URL params
     */
    public function testProcessEmptyUrl()
    {
        $this->event->expects($this->once())
            ->method('getEntity')
            ->willReturn($this->entity);

        $this->event->expects($this->once())
            ->method('getResultItem')
            ->willReturn($this->item);

        $this->item->expects($this->once())
            ->method('getRecordUrl')
            ->willReturn(null);

        $this->item->expects($this->exactly(2))
            ->method('getEntityName')
            ->willReturn(get_class($this->entity));

        $this->em->expects($this->never())
            ->method('getClassMetadata');

        $this->mapper->expects($this->once())
            ->method('getEntityMapParameter')
            ->with(get_class($this->entity), 'route')
            ->willReturn(false);

        $this->item->expects($this->once())
            ->method('setRecordUrl')
            ->with('');

        $config = new Config(new EntityConfigId('entity', User::class));
        $config->set('label', 'testLabel');

        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->willReturn($config);

        $this->listener->process($this->event);
    }

    /**
     * Trying to find entity and generates parameters from result item
     */
    public function testProcessUrl()
    {
        $this->event->expects($this->once())
            ->method('getEntity')
            ->willReturn(null);

        $this->event->expects($this->once())
            ->method('getResultItem')
            ->willReturn($this->item);

        $this->item->expects($this->once())
            ->method('getRecordUrl')
            ->willReturn(null);

        $this->item->expects($this->exactly(2))
            ->method('getEntityName')
            ->willReturn(get_class($this->entity));

        $metadataMock = $this->createMock(ClassMetadata::class);

        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($this->entity))
            ->willReturn($metadataMock);

        $this->em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->item->expects($this->exactly(2))
            ->method('getRecordId')
            ->willReturn(1);

        $this->mapper->expects($this->atLeastOnce())
            ->method('getEntityMapParameter')
            ->with(get_class($this->entity), 'route')
            ->willReturn(['parameters' => ['parameter' => 'field'], 'name' => 'test_route']);

        $config = new Config(new EntityConfigId('entity', User::class));
        $config->set('label', 'testLabel');

        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->willReturn($config);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('test_route', ['parameter' => '1'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('test_url');

        $this->listener->process($this->event);
    }
}
