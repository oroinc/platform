<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Entity\Repository\ItemRepository;
use Oro\Bundle\TestFrameworkBundle\EventListener\WebsiteSearchItemIndexerListener;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

class WebsiteSearchItemIndexerListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsiteSearchItemIndexerListener
     */
    private $listener;

    /**
     * @var ItemRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $itemRepository;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localizationHelper;

    /**
     * @var IndexEntityEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event;

    /**
     * @var \DateTimeImmutable
     */
    private $datetimeValue;

    protected function setUp()
    {
        /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject $doctrineHelper */
        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemRepository = $this->getMockBuilder(ItemRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(Item::class)
            ->willReturn($this->itemRepository);

        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WebsiteSearchItemIndexerListener($doctrineHelper, $this->localizationHelper);

        $this->event = $this->getMockBuilder(IndexEntityEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->datetimeValue = new \DateTimeImmutable();
    }

    protected function tearDown()
    {
        unset($this->itemRepository, $this->localizationHelper, $this->listener, $this->event, $this->datetimeValue);
    }

    /**
     * @param string $entityClassName
     */
    private function initializeOnWebsiteSearchIndexTest($entityClassName)
    {
        $this->event->expects($this->once())->method('getEntityClass')->willReturn($entityClassName);

        $this->event->expects($this->once())->method('getEntityIds')->willReturn([1]);

        $item = new Item();
        $item->id = 1;
        $item->stringValue   = 'item5@mail.com';
        $item->integerValue  = 5000;
        $item->decimalValue  = 3.14;
        $item->floatValue    = 2.718;
        $item->datetimeValue = $this->datetimeValue;
        $item->phone         = '123-456-789';
        $item->blobValue     = 'resource';

        $this->itemRepository->expects($this->once())->method('getItemsByIds')->willReturn([$item]);

        $localization1 = $this->getMock(Localization::class);
        $localization1->expects($this->any())->method('getId')->willReturn(1);
        $localization2 = $this->getMock(Localization::class);
        $localization2->expects($this->any())->method('getId')->willReturn(2);

        $this->localizationHelper
            ->expects($this->once())
            ->method('getLocalizations')
            ->willReturn([
                $localization1,
                $localization2
            ]);
    }

    public function testOnWebsiteSearchIndexItemClass()
    {
        $this->initializeOnWebsiteSearchIndexTest(Item::class);

        $this->event
            ->expects($this->exactly(10))
            ->method('addField')
            ->withConsecutive(
                [1, 'integer',  'integerValue',  5000],
                [1, 'decimal',  'decimalValue',  3.14],
                [1, 'decimal',  'floatValue',    2.718],
                [1, 'datetime', 'datetimeValue', $this->datetimeValue],
                [1, 'text',     'stringValue_1', 'item5@mail.com'],
                [1, 'text',     'all_text_1',    'item5@mail.com'],
                [1, 'text',     'stringValue_2', 'item5@mail.com'],
                [1, 'text',     'all_text_2',    'item5@mail.com'],
                [1, 'text',     'phone',         '123-456-789'],
                [1, 'text',     'blobValue',     'resource']
            );

        $this->listener->onWebsiteSearchIndex($this->event);
    }

    public function testOnWebsiteSearchIndexNotSupportedClass()
    {
        $this->event->expects($this->once())->method('getEntityClass')->willReturn('stdClass');

        $this->event->expects($this->never())->method('addField');

        $this->listener->onWebsiteSearchIndex($this->event);
    }
}
