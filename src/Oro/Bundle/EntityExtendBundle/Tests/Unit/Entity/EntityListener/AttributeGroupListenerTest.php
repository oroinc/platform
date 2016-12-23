<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\EntityExtendBundle\Entity\AttributeFamily;
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
     * @param AttributeGroup $group
     * @param array $repositoryArgs
     * @param array $repositoryResults
     * @param string $expectedCodeSlug
     */
    public function testPrePersist(
        AttributeGroup $group,
        array $repositoryArgs,
        array $repositoryResults,
        $expectedCodeSlug
    ) {
        $eventArgs = new LifecycleEventArgs($group, $this->em);

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->exactly(count($repositoryArgs)))
            ->method('findOneBy')
            ->withConsecutive(...$repositoryArgs)
            ->willReturnOnConsecutiveCalls(...$repositoryResults);

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
        $group1 = new AttributeGroupStub(1, 'проверка транслитерации!');
        $group1->setAttributeFamily((new AttributeFamily())->setCode('group1Family'));
        $group2 = new AttributeGroupStub(1, '!!!&&&&!!!');
        $group2->setAttributeFamily((new AttributeFamily())->setCode('group2Family'));

        return [
            'code slug generated from label' => [
                'group' => $group1,
                'repositoryArgs' => [
                    [[ 'attributeFamily' => $group1->getAttributeFamily(), 'code' => 'proverka-transliteracii' ]],
                    [[ 'attributeFamily' => $group1->getAttributeFamily(), 'code' => 'proverka-transliteracii1' ]],
                ],
                'repositoryResults' => [$group1, null],
                'expectedSlug' => 'proverka-transliteracii1',
            ],
            'default unique slug created' => [
                'group' => $group2,
                'repositoryArgs' => [
                    [
                        [
                            'attributeFamily' => $group2->getAttributeFamily(),
                            'code' => AttributeGroupListener::DEFAULT_SLUG
                        ]
                    ],
                    [
                        [
                            'attributeFamily' => $group2->getAttributeFamily(),
                            'code' => AttributeGroupListener::DEFAULT_SLUG . 1
                        ]
                    ],
                    [
                        [
                            'attributeFamily' => $group2->getAttributeFamily(),
                            'code' => AttributeGroupListener::DEFAULT_SLUG . 2
                        ]
                    ]
                ],
                'repositoryResults' => [$group2, $group2, null],
                'expectedSlug' => AttributeGroupListener::DEFAULT_SLUG . 2,
            ]
        ];
    }
}
