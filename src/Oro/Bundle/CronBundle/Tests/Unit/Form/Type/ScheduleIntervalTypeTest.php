<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CronBundle\Entity\ScheduleIntervalInterface;
use Oro\Bundle\CronBundle\Form\Type\ScheduleIntervalType;
use Oro\Bundle\CronBundle\Tests\Unit\Stub\ScheduleIntervalStub;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class ScheduleIntervalTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    OroDateTimeType::class => new OroDateTimeType()
                ],
                []
            )
        ];
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $submittedData
     * @param ScheduleIntervalInterface $expected
     * @param ScheduleIntervalInterface|null $data
     */
    public function testSubmit(
        array $submittedData,
        ScheduleIntervalInterface $expected,
        ScheduleIntervalInterface $data = null
    ) {
        if (!$data) {
            $data = new ScheduleIntervalStub();
        }
        $form = $this->factory->create(ScheduleIntervalType::class, $data);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $data = $form->getData();
        $this->assertEquals($expected, $data);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [
                'submittedData' => [
                    'activeAt' => '2016-03-01T22:00:00Z',
                    'deactivateAt' => '2016-03-15T22:00:00Z'
                ],
                'expected' => (new ScheduleIntervalStub())
                    ->setActiveAt(new \DateTime('2016-03-01T22:00:00Z'))
                    ->setDeactivateAt(new \DateTime('2016-03-15T22:00:00Z'))
            ]
        ];
    }

    public function testDataClassNotImplementInterface()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Class stdClass given in data_class option must implement %s',
            ScheduleIntervalInterface::class
        ));

        $this->factory->create(ScheduleIntervalType::class, null, ['data_class' => \stdClass::class]);
    }

    public function testDataClassImplementsInterface()
    {
        $this->factory->create(ScheduleIntervalType::class, null, ['data_class' => ScheduleIntervalStub::class]);
    }
}
