<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Form\Type\SystemEmailTemplateSelectType;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SystemEmailTemplateSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var SystemEmailTemplateSelectType */
    private $type;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRepository;

    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $queryBuilder;

    protected function setUp(): void
    {
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->entityRepository = $this->createMock(EmailTemplateRepository::class);
        $this->em = $this->createMock(EntityManager::class);

        $this->type = new SystemEmailTemplateSelectType($this->em);
    }

    public function testConfigureOptions()
    {
        $this->entityRepository->expects($this->any())
            ->method('getSystemTemplatesQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->entityRepository);

        $resolver = $this->createMock(OptionsResolver::class);
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
        $formBuilder = $this->createMock(FormBuilder::class);
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
