<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Security;

use Oro\Bundle\TagBundle\Security\SecurityProvider;

class SecurityProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider aclDataProvider
     * @param bool $isProtected
     * @param bool $isGranted
     * @param array $expectedAllowedEntities
     */
    public function testApplyAcl($isProtected, $isGranted, $expectedAllowedEntities)
    {
        $entities = array(
            array('entityName' => '\stdClass')
        );
        $tableAlias = 'alias';

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        if ($expectedAllowedEntities) {
            $qb->expects($this->once())
                ->method('andWhere')
                ->with($tableAlias . '.entityName IN(:allowedEntities)')
                ->will($this->returnSelf());
            $qb->expects($this->once())
                ->method('setParameter')
                ->with('allowedEntities', $expectedAllowedEntities)
                ->will($this->returnSelf());
        } else {
            $qb->expects($this->once())
                ->method('andWhere')
                ->with('1 = 0')
                ->will($this->returnSelf());
        }

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('getArrayResult'))
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($entities));

        $searchQb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $searchQb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $searchQb->expects($this->any())
            ->method($this->anything())
            ->will($this->returnSelf());

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($searchQb));
        
        $qb->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $searchSecurityProvider = $this->getMockBuilder('Oro\Bundle\SearchBundle\Security\SecurityProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $searchSecurityProvider->expects($this->once())
            ->method('isProtectedEntity')
            ->with($entities[0]['entityName'])
            ->will($this->returnValue($isProtected));
        if ($isProtected) {
            $searchSecurityProvider->expects($this->once())
                ->method('isGranted')
                ->with(SecurityProvider::ENTITY_PERMISSION, 'Entity:' . $entities[0]['entityName'])
                ->will($this->returnValue($isGranted));
        } else {
            $searchSecurityProvider->expects($this->never())
                ->method('isGranted');
        }

        $provider = new SecurityProvider($searchSecurityProvider);
        $provider->applyAcl($qb, $tableAlias);
    }

    public function aclDataProvider()
    {
        return array(
            'not protected' => array(false, null, array('\stdClass')),
            'protected and granted' => array(true, true, array('\stdClass')),
            'protected not granted' => array(true, false, array()),
        );
    }
}
