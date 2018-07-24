<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Owner\ChainEntityOwnershipDecisionMaker;

class ChainEntityOwnershipDecisionMakerTest extends \PHPUnit\Framework\TestCase
{
    public function testPassOwnershipDecisionMakersThroughConstructor()
    {
        $ownershipDecisionMaker = $this->createMock(
            'Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface'
        );

        $chain = new ChainEntityOwnershipDecisionMaker([$ownershipDecisionMaker]);
        $this->assertAttributeContains($ownershipDecisionMaker, 'ownershipDecisionMakers', $chain);
    }

    public function testAddOwnershipDecisionMaker()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|AccessLevelOwnershipDecisionMakerInterface $maker */
        $maker = $this->createMock(
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
     * @dataProvider isOwnerEntityDataProvider
     *
     * @param string $levelMethod
     * @param bool $result
     */
    public function testIsOwnerEntity($levelMethod, $result)
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|AccessLevelOwnershipDecisionMakerInterface $maker */
        $maker = $this->createMock(
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
    public function isOwnerEntityDataProvider()
    {
        return [
            'positive isOrganization' => [
               'levelMethod' => 'isOrganization',
               'result' => true
            ],
            'negative isOrganization' => [
                'levelMethod' => 'isOrganization',
                'result' => false
            ],
            'positive isBusinessUnit' => [
                'levelMethod' => 'isBusinessUnit',
                'result' => true
            ],
            'negative isBusinessUnit' => [
                'levelMethod' => 'isBusinessUnit',
                'result' => false
            ],
            'positive isUser' => [
                'levelMethod' => 'isUser',
                'result' => true
            ],
            'negative isUser' => [
                'levelMethod' => 'isUser',
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
        $chain->isOrganization(new \stdClass());
    }

    /**
     * @dataProvider isAssociatedWithLevelEntityDataProvider
     *
     * @param string $associatedLevelMethod
     * @param bool $result
     */
    public function testIsAssociatedWithLevelEntity($associatedLevelMethod, $result)
    {
        $user = $this->createMock('Symfony\Component\Security\Core\User\UserInterface');
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
            'positive isAssociatedWithBusinessUnit' => [
                'levelMethod' => 'isAssociatedWithBusinessUnit',
                'result' => true
            ],
            'negative isAssociatedWithBusinessUnit' => [
                'levelMethod' => 'isAssociatedWithBusinessUnit',
                'result' => false
            ],
            'positive isAssociatedWithUser' => [
                'levelMethod' => 'isAssociatedWithUser',
                'result' => true
            ],
            'negative isAssociatedWithUser' => [
                'levelMethod' => 'isAssociatedWithUser',
                'result' => false
            ],
            'positive isAssociatedWithOrganization' => [
                'levelMethod' => 'isAssociatedWithOrganization',
                'result' => true
            ],
            'negative isAssociatedWithOrganization' => [
                'levelMethod' => 'isAssociatedWithOrganization',
                'result' => false
            ]
        ];
    }

    /**
     * @param bool $supports
     * @return AccessLevelOwnershipDecisionMakerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getOwnershipDecisionMakerMock($supports = true)
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|AccessLevelOwnershipDecisionMakerInterface $maker */
        $maker = $this->createMock(
            'Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface'
        );
        $maker->expects($this->atLeastOnce())
            ->method('supports')
            ->willReturn($supports);

        return $maker;
    }
}
