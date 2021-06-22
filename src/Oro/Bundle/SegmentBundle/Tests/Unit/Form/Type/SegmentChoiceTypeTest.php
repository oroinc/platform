<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SegmentChoiceTypeTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS = 'TestEntityClass';

    /** @var SegmentChoiceType */
    private $formType;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->formType = new SegmentChoiceType($this->doctrine, $this->aclHelper);
    }

    public function testConfigureOptions(): void
    {
        $expectedOptions = [
            'placeholder' => 'oro.segment.form.segment_choice.placeholder',
            'entityClass' => 'TestEntityClass',
            'choices' => ['First Segment' => 1, 'Second Segment' => 5],
        ];

        $repo = $this->createMock(SegmentRepository::class);
        $repo->expects($this->once())
            ->method('findByEntity')
            ->with($this->aclHelper, self::ENTITY_CLASS)
            ->willReturn(['First Segment' => 1, 'Second Segment' => 5]);
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(Segment::class)
            ->willReturn($repo);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Segment::class)
            ->willReturn($manager);

        $resolver = new OptionsResolver();
        $resolver->setDefault('choices', []);
        $this->formType->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve(['entityClass' => self::ENTITY_CLASS]);
        foreach ($resolver->getDefinedOptions() as $option) {
            $this->assertArrayHasKey($option, $expectedOptions);
            $this->assertArrayHasKey($option, $resolvedOptions);
            $this->assertEquals($expectedOptions[$option], $resolvedOptions[$option]);
        }
    }

    public function testGetParent(): void
    {
        $this->assertEquals(ChoiceType::class, $this->formType->getParent());
    }
}
