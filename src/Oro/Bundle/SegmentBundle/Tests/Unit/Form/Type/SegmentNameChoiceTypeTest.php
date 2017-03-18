<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\SegmentBundle\Form\Type\SegmentNameChoiceType;

class SegmentNameChoiceTypeTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'TestEntityClass';

    /** @var SegmentNameChoiceType */
    protected $formType;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->formType = new SegmentNameChoiceType($this->registry, self::ENTITY_CLASS);
    }

    public function testConfigureOptions()
    {
        $expectedOptions = [
            'placeholder' => 'oro.segment.form.segment_name_choice.placeholder',
            'entityClass' => 'TestEntityClass',
            'choices' => ['First Segment', 'Second Segment'],
        ];

        $repo = $this->getMockBuilder(ObjectRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByEntity', 'find', 'findAll', 'findBy', 'findOneBy', 'getClassName'])
            ->getMock();
        $repo->expects($this->once())
            ->method('findByEntity')
            ->willReturn(['First Segment', 'Second Segment']);
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(self::ENTITY_CLASS)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
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

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->formType->getParent());
    }
}
