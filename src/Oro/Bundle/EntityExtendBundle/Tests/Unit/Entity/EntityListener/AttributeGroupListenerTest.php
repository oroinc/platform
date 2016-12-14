<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\EntityExtendBundle\Entity\AttributeGroup;
use Oro\Bundle\EntityExtendBundle\Entity\EntityListener\AttributeGroupListener;
use Oro\Bundle\EntityExtendBundle\Generator\SlugGenerator;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints\AttributeGroupStub;

class AttributeGroupListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttributeGroupListener */
    private $listener;

    /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $em;

    protected function setUp()
    {
        $this->listener = new AttributeGroupListener(new SlugGenerator());
        $this->em = $this->getMock(EntityManagerInterface::class);
    }

    public function testPrePersistCodeExist()
    {
        $group = new AttributeGroupStub(1, 'label');
        $group->setCode('some-code');
        $eventArgs = new LifecycleEventArgs($group, $this->em);
        $this->em->expects($this->never())->method('getRepository');
        $this->listener->prePersist($group, $eventArgs);
    }

    /**
     * @dataProvider prePersistDataProvider
     * @param string $label
     * @param string $initialCodeSlug
     * @param string $expectedCodeSlug
     */
    public function testPrePersist($label, $initialCodeSlug, $expectedCodeSlug)
    {
        $group = new AttributeGroupStub(1, $label);
        $eventArgs = new LifecycleEventArgs($group, $this->em);

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive(
                [
                    [ 'attributeFamily' => $group->getAttributeFamily(), 'code' => $initialCodeSlug ]
                ],
                [
                    [ 'attributeFamily' => $group->getAttributeFamily(), 'code' => $initialCodeSlug . 1 ]
                ]
            )
            ->willReturnOnConsecutiveCalls($group, null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(AttributeGroup::class)
            ->willReturn($repository);

        $this->listener->prePersist($group, $eventArgs);
        $this->assertEquals($expectedCodeSlug, $group->getCode());
    }

    /**
     * @return array
     */
    public function prePersistDataProvider()
    {
        return [
            'code slug generated from label' => [
                'label' => 'проверка транслитерации!',
                'initialSlug' => 'proverka-transliteracii',
                'expectedSlug' => 'proverka-transliteracii1',
            ],
            'default unique slug created' => [
                'label' => '!!!&&&&!!!',
                'initialSlug' => AttributeGroupListener::DEFAULT_SLUG,
                'expectedSlug' => AttributeGroupListener::DEFAULT_SLUG . 1,
            ]
        ];
    }
}
