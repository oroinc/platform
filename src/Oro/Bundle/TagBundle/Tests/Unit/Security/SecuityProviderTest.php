<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Security;

use Oro\Bundle\TagBundle\Security\SecurityProvider;

class SecurityProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider aclDataProvider
     * @param array $protectedMap
     * @param array $grantedMap
     * @param array $expectedAllowedEntities
     */
    public function testApplyAcl($protectedMap, $grantedMap, $expectedAllowedEntities)
    {
        $entities = array(
            array('entityName' => '\stdClass'),
            array('entityName' => '\DateTime')
        );
        $tableAlias = 'alias';

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

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

        if ($expectedAllowedEntities) {
            if (count($expectedAllowedEntities) != count($entities)) {
                $qb->expects($this->once())
                    ->method('andWhere')
                    ->with($tableAlias . '.entityName IN(:allowedEntities)')
                    ->will($this->returnSelf());
                $qb->expects($this->once())
                    ->method('setParameter')
                    ->with('allowedEntities', $expectedAllowedEntities)
                    ->will($this->returnSelf());
            }
        } else {
            $qb->expects($this->once())
                ->method('andWhere')
                ->with('1 = 0')
                ->will($this->returnSelf());
        }

        $searchSecurityProvider = $this->getMockBuilder('Oro\Bundle\SearchBundle\Security\SecurityProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $searchSecurityProvider->expects($this->exactly(count($entities)))
            ->method('isProtectedEntity')
            ->will($this->returnValueMap($protectedMap));

        if ($grantedMap) {
            $searchSecurityProvider->expects($this->exactly(count($grantedMap)))
                ->method('isGranted')
                ->will($this->returnValueMap($grantedMap));
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
            'not protected' => array(
                array(),
                array(),
                array('\stdClass', '\DateTime')
            ),
            'protected and granted' => array(
                array(
                    array('\stdClass', true),
                    array('\DateTime', true)
                ),
                array(
                    array(SecurityProvider::ENTITY_PERMISSION, 'Entity:\stdClass', true),
                    array(SecurityProvider::ENTITY_PERMISSION, 'Entity:\DateTime', true)
                ),
                array('\stdClass', '\DateTime')
            ),
            'protected not granted' => array(
                array(
                    array('\stdClass', true),
                    array('\DateTime', true)
                ),
                array(
                    array(SecurityProvider::ENTITY_PERMISSION, 'Entity:\stdClass', false),
                    array(SecurityProvider::ENTITY_PERMISSION, 'Entity:\DateTime', false)
                ),
                array()
            ),
            'one not protected other granted' => array(
                array(
                    array('\stdClass', false),
                    array('\DateTime', true)
                ),
                array(
                    array(SecurityProvider::ENTITY_PERMISSION, 'Entity:\DateTime', true)
                ),
                array('\stdClass', '\DateTime')
            ),
            'both protected one granted' => array(
                array(
                    array('\stdClass', true),
                    array('\DateTime', true)
                ),
                array(
                    array(SecurityProvider::ENTITY_PERMISSION, 'Entity:\stdClass', false),
                    array(SecurityProvider::ENTITY_PERMISSION, 'Entity:\DateTime', true)
                ),
                array('\DateTime')
            ),
        );
    }
}
