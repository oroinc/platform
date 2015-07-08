<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Owner\ChainEntityOwnershipDecisionMaker;

class ChainEntityOwnershipDecisionMakerTest extends \PHPUnit_Framework_TestCase
{
    public function testPassOwnershipDecisionMakersThroughConstructor()
    {
        $ownershipDecisionMaker = $this->getMock(
            'Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface'
        );

        $chain = new ChainEntityOwnershipDecisionMaker([$ownershipDecisionMaker]);
        $this->assertAttributeContains($ownershipDecisionMaker, 'ownershipDecisionMakers', $chain);
    }

    public function testAddOwnershipDecisionMaker()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AccessLevelOwnershipDecisionMakerInterface $maker */
        $maker = $this->getMock(
            'Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface'
        );

        $chain = new ChainEntityOwnershipDecisionMaker([$maker]);
        $chain->addOwnershipDecisionMaker($maker);
        $anotherMaker = clone $maker;
        $chain->addOwnershipDecisionMaker($anotherMaker);

        $this->assertAttributeContains($maker, 'ownershipDecisionMakers', $chain);
        $this->assertAttributeContains($anotherMaker, 'ownershipDecisionMakers', $chain);
    }

    public function testSupports()
    {
        $chain = new ChainEntityOwnershipDecisionMaker();
        $this->assertFalse($chain->supports());
        $chain->addOwnershipDecisionMaker($this->getOwnershipDecisionMakerMock(false));
        $this->assertFalse($chain->supports());

        $chain->addOwnershipDecisionMaker($this->getOwnershipDecisionMakerMock(true));
        $this->assertTrue($chain->supports());
    }

    /**
     * @dataProvider isLevelEntityDataProvider
     *
     * @param string $levelMethod
     * @param bool $result
     */
    public function testIsLevelEntity($levelMethod, $result)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AccessLevelOwnershipDecisionMakerInterface $maker */
        $maker = $this->getMock(
            'Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface'
        );
        $maker->expects($this->once())
            ->method('supports')
            ->willReturn(true);
        $maker->expects($this->atLeastOnce())
            ->method($levelMethod)
            ->will($this->returnValue($result));

        $chain = new ChainEntityOwnershipDecisionMaker();
        $chain->addOwnershipDecisionMaker($maker);
        $this->assertEquals($result, $chain->$levelMethod(new \stdClass()));
        $this->assertEquals($result, $chain->$levelMethod(new \stdClass()));
    }

    /**
     * @return array
     */
    public function isLevelEntityDataProvider()
    {
        return [
            'positive isGlobalLevelEntity' => [
               'levelMethod' => 'isGlobalLevelEntity',
               'result' => true
            ],
            'negative isGlobalLevelEntity' => [
                'levelMethod' => 'isGlobalLevelEntity',
                'result' => false
            ],
            'positive isLocalLevelEntity' => [
                'levelMethod' => 'isLocalLevelEntity',
                'result' => true
            ],
            'negative isLocalLevelEntity' => [
                'levelMethod' => 'isLocalLevelEntity',
                'result' => false
            ],
            'positive isBasicLevelEntity' => [
                'levelMethod' => 'isBasicLevelEntity',
                'result' => true
            ],
            'negative isBasicLevelEntity' => [
                'levelMethod' => 'isBasicLevelEntity',
                'result' => false
            ]
        ];
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\NotFoundSupportedOwnershipDecisionMakerException
     * @expectedExceptionMessage Not found supported ownership decision maker in chain
     */
    public function testNotFoundSupportedOwnershipDecisionMakerException()
    {
        $maker = $this->getOwnershipDecisionMakerMock(false);
        $chain = new ChainEntityOwnershipDecisionMaker();
        $chain->addOwnershipDecisionMaker($maker);
        $chain->isGlobalLevelEntity(new \stdClass());
    }

    /**
     * @dataProvider isAssociatedWithLevelEntityDataProvider
     *
     * @param string $associatedLevelMethod
     * @param bool $result
     */
    public function testIsAssociatedWithLevelEntity($associatedLevelMethod, $result)
    {
        $user = $this->getMock('Symfony\Component\Security\Core\UserUserInterface');
        $domainObject = new \stdClass();

        $maker = $this->getOwnershipDecisionMakerMock(true);
        $maker->expects($this->once())
            ->method($associatedLevelMethod)
            ->will($this->returnValue($result));

        $chain = new ChainEntityOwnershipDecisionMaker();
        $chain->addOwnershipDecisionMaker($maker);
        $this->assertEquals(
            $result,
            $chain->$associatedLevelMethod(
                $user,
                $domainObject
            )
        );
    }

    public function isAssociatedWithLevelEntityDataProvider()
    {
        return [
            'positive isAssociatedWithLocalLevelEntity' => [
                'levelMethod' => 'isAssociatedWithLocalLevelEntity',
                'result' => true
            ],
            'negative isAssociatedWithLocalLevelEntity' => [
                'levelMethod' => 'isAssociatedWithLocalLevelEntity',
                'result' => false
            ],
            'positive isAssociatedWithBasicLevelEntity' => [
                'levelMethod' => 'isAssociatedWithBasicLevelEntity',
                'result' => true
            ],
            'negative isAssociatedWithBasicLevelEntity' => [
                'levelMethod' => 'isAssociatedWithBasicLevelEntity',
                'result' => false
            ],
            'positive isAssociatedWithGlobalLevelEntity' => [
                'levelMethod' => 'isAssociatedWithGlobalLevelEntity',
                'result' => true
            ],
            'negative isAssociatedWithGlobalLevelEntity' => [
                'levelMethod' => 'isAssociatedWithGlobalLevelEntity',
                'result' => false
            ]
        ];
    }

    /**
     * @param bool $supports
     * @return AccessLevelOwnershipDecisionMakerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getOwnershipDecisionMakerMock($supports = true)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AccessLevelOwnershipDecisionMakerInterface $maker */
        $maker = $this->getMock(
            'Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface'
        );
        $maker->expects($this->atLeastOnce())
            ->method('supports')
            ->willReturn($supports);

        return $maker;
    }
}
