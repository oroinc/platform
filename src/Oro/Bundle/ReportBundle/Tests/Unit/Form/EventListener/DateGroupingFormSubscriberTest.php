<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\QueryDesignerBundle\Form\Type\DateGroupingType;
use Oro\Bundle\QueryDesignerBundle\Model\DateGrouping;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Form\EventListener\DateGroupingFormSubscriber;
use Oro\Bundle\ReportBundle\Form\Type\ReportType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class DateGroupingFormSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var DateGroupingFormSubscriber */
    private $dateGroupingFormSubscriber;

    /** @var FormEvent|\PHPUnit\Framework\MockObject\MockObject */
    private $event;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dateForm;

    protected function setUp(): void
    {
        $this->dateGroupingFormSubscriber = new DateGroupingFormSubscriber();
        $this->event = $this->createMock(FormEvent::class);
        $this->dateForm = $this->createMock(FormInterface::class);
        $this->form = $this->createMock(FormInterface::class);
        $this->form->expects($this->any())
            ->method('get')
            ->with(ReportType::DATE_GROUPING_FORM_NAME)
            ->willReturn($this->dateForm);
        $this->event->expects($this->once())
            ->method('getForm')
            ->willReturn($this->form);
        $this->form->expects($this->any())
            ->method('has')
            ->with(ReportType::DATE_GROUPING_FORM_NAME)
            ->willReturn(true);
    }

    public function testOnPostSetDataReturnsNull()
    {
        $this->form->expects($this->never())->method('get');
        $this->assertNull($this->dateGroupingFormSubscriber->onPostSetData($this->event));
    }

    public function testOnPostSetDataDisablesFilter()
    {
        $report = new Report();
        $report->setDefinition(QueryDefinitionUtil::encodeDefinition([]));
        $this->event->expects($this->once())
            ->method('getData')
            ->willReturn($report);

        $this->dateForm->expects($this->once())
            ->method('setData')
            ->willReturnCallback(
                function (DateGrouping $dateModel) {
                    $this->assertFalse($dateModel->getUseDateGroupFilter());
                }
            );
        $this->dateGroupingFormSubscriber->onPostSetData($this->event);
    }

    public function testOnPostSetDataDisablesEnablesFilter()
    {
        $report = new Report();
        $report->setDefinition(QueryDefinitionUtil::encodeDefinition([
            DateGroupingType::DATE_GROUPING_NAME => [
                DateGroupingType::FIELD_NAME_ID => 'testFieldName',
                DateGroupingType::USE_SKIP_EMPTY_PERIODS_FILTER_ID => true,
            ]
        ]));
        $this->event->expects($this->once())
            ->method('getData')
            ->willReturn($report);

        $this->dateForm->expects($this->once())
            ->method('setData')
            ->willReturnCallback(
                function (DateGrouping $dateModel) {
                    $this->assertTrue($dateModel->getUseDateGroupFilter());
                    $this->assertTrue($dateModel->getUseSkipEmptyPeriodsFilter());
                    $this->assertEquals('testFieldName', $dateModel->getFieldName());
                }
            );
        $this->dateGroupingFormSubscriber->onPostSetData($this->event);
    }

    public function testOnPostSetDataDisablesEnablesFilterWithExistingDateGroupingModel()
    {
        $report = new Report();
        $report->setDefinition(QueryDefinitionUtil::encodeDefinition([
            DateGroupingType::DATE_GROUPING_NAME => [
                DateGroupingType::FIELD_NAME_ID => 'newValue',
                DateGroupingType::USE_SKIP_EMPTY_PERIODS_FILTER_ID => false,
            ]
        ]));
        $this->event->expects($this->once())
            ->method('getData')
            ->willReturn($report);
        $originalDateModel = new DateGrouping();
        $originalDateModel->setFieldName('oldValue');
        $originalDateModel->setUseSkipEmptyPeriodsFilter(true);

        $this->dateForm->expects($this->once())
            ->method('getData')
            ->willReturn($originalDateModel);

        $this->dateForm->expects($this->once())
            ->method('setData')
            ->willReturnCallback(
                function (DateGrouping $dateModel) use ($originalDateModel) {
                    $this->assertSame($originalDateModel, $dateModel);
                    $this->assertTrue($dateModel->getUseDateGroupFilter());
                    $this->assertFalse($dateModel->getUseSkipEmptyPeriodsFilter());
                    $this->assertEquals('newValue', $dateModel->getFieldName());
                }
            );
        $this->dateGroupingFormSubscriber->onPostSetData($this->event);
    }

    public function testOnSubmitReturnsNull()
    {
        $this->form->expects($this->never())
            ->method('get');
        $this->assertNull($this->dateGroupingFormSubscriber->onSubmit($this->event));
    }

    public function testOnSubmitRemovesFilterDefinition()
    {
        $report = new Report();
        $report->setDefinition(QueryDefinitionUtil::encodeDefinition([
            DateGroupingType::DATE_GROUPING_NAME => []
        ]));
        $originalDateModel = new DateGrouping();
        $originalDateModel->setUseDateGroupFilter(false);

        $this->dateForm->expects($this->once())
            ->method('getData')
            ->willReturn($originalDateModel);

        $this->event->expects($this->once())
            ->method('getData')
            ->willReturn($report);

        $this->dateGroupingFormSubscriber->onSubmit($this->event);
        $this->assertArrayNotHasKey(
            DateGroupingType::DATE_GROUPING_NAME,
            QueryDefinitionUtil::decodeDefinition($report->getDefinition())
        );
    }

    public function testOnSubmitUpdatesDefinition()
    {
        $report = new Report();
        $report->setDefinition(QueryDefinitionUtil::encodeDefinition([
            DateGroupingType::DATE_GROUPING_NAME => [
                DateGroupingType::FIELD_NAME_ID => 'oldValue',
                DateGroupingType::USE_SKIP_EMPTY_PERIODS_FILTER_ID => false,
            ]
        ]));
        $this->event->expects($this->once())
            ->method('getData')
            ->willReturn($report);
        $originalDateModel = new DateGrouping();
        $originalDateModel->setFieldName('newValue');
        $originalDateModel->setUseSkipEmptyPeriodsFilter(true);
        $originalDateModel->setUseDateGroupFilter(true);

        $this->dateForm->expects($this->once())
            ->method('getData')
            ->willReturn($originalDateModel);

        $this->dateGroupingFormSubscriber->onSubmit($this->event);
        $newDefinition = QueryDefinitionUtil::decodeDefinition($report->getDefinition());
        $this->assertArrayHasKey(DateGroupingType::DATE_GROUPING_NAME, $newDefinition);
        $this->assertTrue(
            $newDefinition[DateGroupingType::DATE_GROUPING_NAME][DateGroupingType::USE_SKIP_EMPTY_PERIODS_FILTER_ID]
        );
        $this->assertTrue(
            $newDefinition[DateGroupingType::DATE_GROUPING_NAME][DateGroupingType::USE_DATE_GROUPING_FILTER]
        );
        $this->assertEquals(
            'newValue',
            $newDefinition[DateGroupingType::DATE_GROUPING_NAME][DateGroupingType::FIELD_NAME_ID]
        );
    }
}
