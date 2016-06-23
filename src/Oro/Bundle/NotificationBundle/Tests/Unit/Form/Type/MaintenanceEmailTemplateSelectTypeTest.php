<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\NotificationBundle\Form\Type\MaintenanceEmailTemplateSelectType;

class MaintenanceEmailTemplateSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MaintenanceEmailTemplateSelectType
     */
    protected $type;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->setMethods(['getOrganization'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $this->type = new MaintenanceEmailTemplateSelectType($this->em, $this->securityFacade);
    }

    protected function tearDown()
    {
        unset($this->type);
        unset($this->em);
        unset($this->securityFacade);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_notification_maintenance_template_list', $this->type->getName());
    }

    public function testSetDefaultOptionsWithDefaultTemplate()
    {
        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $this->securityFacade->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue($organization));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
                ->disableOriginalConstructor()
                ->getMock();

        $entityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
                ->disableOriginalConstructor()
                ->setMethods(['getEntityTemplatesQueryBuilder'])
                ->getMock();

        $entityRepository->expects($this->any())
            ->method('getEntityTemplatesQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($entityRepository));
        
        $this->type->setDefaultTemplate('default_template_name');
        $queryBuilder->expects($this->at(0))->method('orWhere')->with('e.name = :default_template')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->at(1))->method('setParameter')->with('default_template', 'default_template_name');
        
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $this->type->setDefaultOptions($resolver);
    }
}
