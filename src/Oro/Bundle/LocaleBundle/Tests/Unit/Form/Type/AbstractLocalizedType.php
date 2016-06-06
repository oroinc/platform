<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\LocaleBundle\Entity\Localization;

abstract class AbstractLocalizedType extends FormIntegrationTestCase
{
    const LOCALIZATION_CLASS = 'Oro\Bundle\LocaleBundle\Entity\Localization';

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
            ->willReturn($this->getLocalizations());

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with('l.parentLocalization', 'parent')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('l.id', 'ASC')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('l')
            ->will($this->returnValue($queryBuilder));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(self::LOCALIZATION_CLASS)
            ->will($this->returnValue($repository));
    }

    /**
     * @return Localization[]
     */
    protected function getLocalizations()
    {
        $en   = $this->createLocalization(1, 'en', 'en');
        $enUs = $this->createLocalization(2, 'en', 'en_US', $en);
        $enCa = $this->createLocalization(3, 'en', 'en_CA', $en);

        return [$en, $enUs, $enCa];
    }

    /**
     * @param int $id
     * @param string $languageCode
     * @param string $formattingCode
     * @param Localization|null $parentLocalization
     * @return Localization
     */
    protected function createLocalization($id, $languageCode, $formattingCode, $parentLocalization = null)
    {
        $website = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Entity\Localization')
            ->disableOriginalConstructor()
            ->getMock();
        $website->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        $website->expects($this->any())
            ->method('getLanguageCode')
            ->will($this->returnValue($languageCode));
        $website->expects($this->any())
            ->method('getFormattingCode')
            ->will($this->returnValue($formattingCode));
        $website->expects($this->any())
            ->method('getParentLocalization')
            ->will($this->returnValue($parentLocalization));

        return $website;
    }
}
