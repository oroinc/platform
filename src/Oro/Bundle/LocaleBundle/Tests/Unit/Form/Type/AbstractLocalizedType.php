<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

abstract class AbstractLocalizedType extends FormIntegrationTestCase
{
    const LOCALE_CLASS = 'OroB2B\Bundle\WebsiteBundle\Entity\Locale';

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @return ManagerRegistry
     */
    protected function setRegistryExpectations()
    {
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($this->getLocales()));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with('locale.parentLocale', 'parentLocale')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('locale.id', 'ASC')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('locale')
            ->will($this->returnValue($queryBuilder));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(self::LOCALE_CLASS)
            ->will($this->returnValue($repository));
    }

    /**
     * @return Locale[]
     */
    protected function getLocales()
    {
        $en   = $this->createLocale(1, 'en');
        $enUs = $this->createLocale(2, 'en_US', $en);
        $enCa = $this->createLocale(3, 'en_CA', $en);

        return [$en, $enUs, $enCa];
    }

    /**
     * @param int $id
     * @param string $code
     * @param Locale|null $parentLocale
     * @return Locale
     */
    protected function createLocale($id, $code, $parentLocale = null)
    {
        $website = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Entity\Locale')
            ->disableOriginalConstructor()
            ->getMock();
        $website->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        $website->expects($this->any())
            ->method('getCode')
            ->will($this->returnValue($code));
        $website->expects($this->any())
            ->method('getParentLocale')
            ->will($this->returnValue($parentLocale));

        return $website;
    }
}
