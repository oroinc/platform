<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Util;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Validator\Constraints\Sorting as SortingConstraint;
use Oro\Bundle\SegmentBundle\Validator\SortingValidator;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class SortingValidatorTest extends ConstraintValidatorTestCase
{
    /** @var SortingConstraint */
    protected $sortingConstraint;

    /** @var Segment|\PHPUnit\Framework\MockObject\MockObject */
    protected $segment;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator        = $this->createMock(TranslatorInterface::class);
        $this->segment           = $this->createMock(Segment::class);
        $this->sortingConstraint = new SortingConstraint();

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->translator, $this->sortingConstraint, $this->segment);

        parent::tearDown();
    }

    /**
     * @return SortingValidator
     */
    protected function createValidator()
    {
        return new SortingValidator($this->translator);
    }

    /**
     * @test
     */
    public function notSegmentInstance()
    {
        $this->validator->validate(new \stdClass(), $this->sortingConstraint);
        $this->assertNoViolation();
    }

    /**
     * @test
     */
    public function withoutRecordsLimit()
    {
        $this->segment
            ->method('getRecordsLimit')
            ->will($this->returnValue(null));

        $this->validator->validate($this->segment, $this->sortingConstraint);
        $this->assertNoViolation();
    }

    /**
     * @test
     */
    public function withRecordsLimitAndWithoutDefinition()
    {
        $this->segment
            ->method('getRecordsLimit')
            ->will($this->returnValue(10));
        $this->segment
            ->method('getDefinition')
            ->will($this->returnValue(null));

        $this->validator->validate($this->segment, $this->sortingConstraint);
        $this->assertNoViolation();
    }

    /**
     * @test
     */
    public function withRecordsLimitAndWithoutSortedColumns()
    {
        $message = 'Please specify sorting for at least one column.';
        $this->translator
            ->method('trans')
            ->will($this->returnValue($message));
        $this->segment
            ->method('getRecordsLimit')
            ->will($this->returnValue(10));
        $this->segment
            ->method('getDefinition')
            ->will($this->returnValue('{"columns":[{"name":"field","label":"Field name","sorting":"","func":null}]}'));

        $this->validator->validate($this->segment, $this->sortingConstraint);
        $this->buildViolation($message)
            ->assertRaised();
    }

    /**
     * @test
     */
    public function withRecordsLimitAndWithSortedAndUnsortedColumns()
    {
        $this->segment
            ->method('getRecordsLimit')
            ->will($this->returnValue(10));
        $this->segment
            ->method('getDefinition')
            ->will(
                $this->returnValue(
                    '{"columns":[{"name":"field","label":"Field","sorting":"asc","func":null},'.
                    '{"name":"field2","label":"Field2","sorting":"","func":null}]}'
                )
            );

        $this->validator->validate($this->segment, $this->sortingConstraint);
        $this->assertNoViolation();
    }

    /**
     * @test
     */
    public function withRecordsLimitAndSortedColumns()
    {
        $this->segment
            ->method('getRecordsLimit')
            ->will($this->returnValue(10));
        $this->segment
            ->method('getDefinition')
            ->will($this->returnValue('{"columns":[{"name":"field","label":"Field","sorting":"asc","func":null}]}'));

        $this->validator->validate($this->segment, $this->sortingConstraint);
        $this->assertNoViolation();
    }
}
