<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentChoiceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SegmentChoiceTypeTest extends TestCase
{
    private const ENTITY_CLASS = 'TestEntityClass';

    private ManagerRegistry&MockObject $doctrine;
    private AclHelper&MockObject $aclHelper;
    private SegmentChoiceType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->formType = new SegmentChoiceType($this->doctrine, $this->aclHelper);
    }

    public function testConfigureOptionsWhenNotEntityChoices(): void
    {
        $expectedOptions = [
            'placeholder' => 'oro.segment.form.segment_choice.placeholder',
            'entityClass' => 'TestEntityClass',
            'entityChoices' => false,
            'choices' => ['First Segment' => 1, 'Second Segment' => 5],
            'choice_label' => '',
            'choice_value' => '',
        ];

        $repo = $this->createMock(SegmentRepository::class);
        $repo->expects(self::once())
            ->method('findByEntity')
            ->with($this->aclHelper, self::ENTITY_CLASS)
            ->willReturn(['First Segment' => 1, 'Second Segment' => 5]);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Segment::class)
            ->willReturn($repo);

        $resolver = new OptionsResolver();
        $resolver->setDefault('choices', []);
        $this->formType->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve(['entityClass' => self::ENTITY_CLASS]);
        foreach ($resolver->getDefinedOptions() as $option) {
            self::assertArrayHasKey($option, $expectedOptions);
            self::assertArrayHasKey($option, $resolvedOptions);
            self::assertEquals($expectedOptions[$option], $resolvedOptions[$option]);
        }
    }

    public function testConfigureOptionsWhenEntityChoices(): void
    {
        $segment1 = (new Segment())->setName('First Segment');
        $segment2 = (new Segment())->setName('Second Segment');
        $expectedOptions = [
            'placeholder' => 'oro.segment.form.segment_choice.placeholder',
            'entityClass' => 'TestEntityClass',
            'entityChoices' => true,
            'choices' => [1 => $segment1, 5 => $segment2],
            'choice_label' => 'name',
            'choice_value' => 'id',
        ];

        $repo = $this->createMock(SegmentRepository::class);
        $repo->expects(self::once())
            ->method('findSegmentsByEntity')
            ->with($this->aclHelper, self::ENTITY_CLASS)
            ->willReturn([1 => $segment1, 5 => $segment2]);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Segment::class)
            ->willReturn($repo);

        $resolver = new OptionsResolver();
        $resolver->setDefault('choices', []);
        $this->formType->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve(['entityClass' => self::ENTITY_CLASS, 'entityChoices' => true]);
        foreach ($resolver->getDefinedOptions() as $option) {
            self::assertArrayHasKey($option, $expectedOptions);
            self::assertArrayHasKey($option, $resolvedOptions);
            self::assertEquals($expectedOptions[$option], $resolvedOptions[$option]);
        }
    }

    public function testGetParent(): void
    {
        self::assertEquals(ChoiceType::class, $this->formType->getParent());
    }
}
