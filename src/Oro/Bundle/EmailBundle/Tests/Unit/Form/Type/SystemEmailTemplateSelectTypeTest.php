<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Form\Type\SystemEmailTemplateSelectType;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;

class SystemEmailTemplateSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SystemEmailTemplateSelectType
     */
    protected $type;

    /**
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $em;

    /**
     * @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityRepository;

    /**
     * @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $queryBuilder;

    /**
     * Setup test env
     */
    protected function setUp()
    {
        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
                ->disableOriginalConstructor()
                ->getMock();

        $this->entityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
                ->disableOriginalConstructor()
                ->setMethods(['getSystemTemplatesQueryBuilder'])
                ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $this->type = new SystemEmailTemplateSelectType($this->em);
    }

    public function testConfigureOptions()
    {
        $this->entityRepository->expects($this->any())
            ->method('getSystemTemplatesQueryBuilder')
            ->will($this->returnValue($this->queryBuilder));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->entityRepository));

        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'query_builder' => $this->queryBuilder,
                'class'         => 'OroEmailBundle:EmailTemplate',
                'choice_value'  => 'name',
                'choice_label'  => 'name'
            ]);
        $this->type->configureOptions($resolver);
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
        $this->assertEquals(Select2TranslatableEntityType::class, $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_email_system_template_list', $this->type->getName());
    }
}
