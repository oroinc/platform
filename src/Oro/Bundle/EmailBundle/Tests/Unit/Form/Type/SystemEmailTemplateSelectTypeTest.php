<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EmailBundle\Form\Type\SystemEmailTemplateSelectType;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class SystemEmailTemplateSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SystemEmailTemplateSelectType
     */
    protected $type;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityRepository;

    /**
     * @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $queryBuilder;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var Organization|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $organization;

    /**
     * Setup test env
     */
    protected function setUp()
    {
        $this->organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->setMethods(['getOrganization'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
                ->disableOriginalConstructor()
                ->getMock();

        $this->entityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
                ->disableOriginalConstructor()
                ->setMethods(['getEntityTemplatesQueryBuilder'])
                ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $this->type = new SystemEmailTemplateSelectType($this->em, $this->securityFacade);
    }

    protected function tearDown()
    {
        unset($this->type);
        unset($this->securityFacade);
        unset($this->em);
        unset($this->queryBuilder);
        unset($this->entityRepository);
        unset($this->organization);
    }

    public function testSetDefaultOptions()
    {
        $this->securityFacade->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue($this->organization));

        $this->entityRepository->expects($this->any())
            ->method('getEntityTemplatesQueryBuilder')
            ->with(
                '',
                $this->organization,
                true
            )
            ->will($this->returnValue($this->queryBuilder));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->entityRepository));

        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'query_builder' => $this->queryBuilder,
                'class'         => 'OroEmailBundle:EmailTemplate',
                'choice_value'  => 'name'
            ]);
        $this->type->setDefaultOptions($resolver);
    }
    
    public function testBuildForm()
    {
        $formBuilder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['addModelTransformer'])
            ->getMock();

        $formBuilder->expects($this->once())
            ->method('addModelTransformer');

        $this->type->buildForm($formBuilder, []);

        unset($formBuilder);
    }

    public function testGetParent()
    {
        $this->assertEquals('genemu_jqueryselect2_translatable_entity', $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_email_system_template_list', $this->type->getName());
    }
}
