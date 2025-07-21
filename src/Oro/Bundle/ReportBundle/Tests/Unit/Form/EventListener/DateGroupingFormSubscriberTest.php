<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\QueryDesignerBundle\Form\Type\DateGroupingType;
use Oro\Bundle\QueryDesignerBundle\Model\DateGrouping;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Form\EventListener\DateGroupingFormSubscriber;
use Oro\Bundle\ReportBundle\Form\Type\ReportType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class DateGroupingFormSubscriberTest extends TestCase
{
    private DateGroupingFormSubscriber $dateGroupingFormSubscriber;
    private FormEvent&MockObject $event;
    private FormInterface&MockObject $form;
    private FormInterface&MockObject $dateForm;

    #[\Override]
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

    public function testOnPostSetDataReturnsNull(): void
    {
        $this->form->expects($this->never())
            ->method('get');
        $this->dateGroupingFormSubscriber->onPostSetData($this->event);
    }

    public function testOnPostSetDataDisablesFilter(): void
    {
        $report = new Report();
        $report->setDefinition(QueryDefinitionUtil::encodeDefinition([]));
        $this->event->expects($this->once())
            ->method('getData')
            ->willReturn($report);

        $dateForm = $this->dateForm;
        $dateForm->expects($this->once())
            ->method('setData')
            ->willReturnCallback(function (DateGrouping $dateModel) use ($dateForm) {
                $this->assertFalse($dateModel->getUseDateGroupFilter());

                return $dateForm;
            });
        $this->dateGroupingFormSubscriber->onPostSetData($this->event);
    }

    public function testOnPostSetDataDisablesEnablesFilter(): void
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

        $dateForm = $this->dateForm;
        $dateForm->expects($this->once())
            ->method('setData')
            ->willReturnCallback(function (DateGrouping $dateModel) use ($dateForm) {
                $this->assertTrue($dateModel->getUseDateGroupFilter());
                $this->assertTrue($dateModel->getUseSkipEmptyPeriodsFilter());
                $this->assertEquals('testFieldName', $dateModel->getFieldName());

                return $dateForm;
            });
        $this->dateGroupingFormSubscriber->onPostSetData($this->event);
    }

    public function testOnPostSetDataDisablesEnablesFilterWithExistingDateGroupingModel(): void
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

        $dateForm = $this->dateForm;
        $dateForm->expects($this->once())
            ->method('setData')
            ->willReturnCallback(function (DateGrouping $dateModel) use ($originalDateModel, $dateForm) {
                $this->assertSame($originalDateModel, $dateModel);
                $this->assertTrue($dateModel->getUseDateGroupFilter());
                $this->assertFalse($dateModel->getUseSkipEmptyPeriodsFilter());
                $this->assertEquals('newValue', $dateModel->getFieldName());

                return $dateForm;
            });
        $this->dateGroupingFormSubscriber->onPostSetData($this->event);
    }

    public function testOnSubmitReturnsNull(): void
    {
        $this->form->expects($this->never())
            ->method('get');
        $this->assertNull($this->dateGroupingFormSubscriber->onSubmit($this->event));
    }

    public function testOnSubmitRemovesFilterDefinition(): void
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

    public function testOnSubmitUpdatesDefinition(): void
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
