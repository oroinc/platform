<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Form\Type\SystemEmailTemplateSelectType;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SystemEmailTemplateSelectTypeTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private EntityRepository&MockObject $entityRepository;
    private QueryBuilder&MockObject $queryBuilder;
    private SystemEmailTemplateSelectType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->entityRepository = $this->createMock(EmailTemplateRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->type = new SystemEmailTemplateSelectType($this->em);
    }

    public function testConfigureOptions(): void
    {
        $this->entityRepository->expects(self::any())
            ->method('getSystemTemplatesQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->em->expects(self::once())
            ->method('getRepository')
            ->willReturn($this->entityRepository);

        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with([
                'query_builder' => $this->queryBuilder,
                'class'         => EmailTemplate::class,
                'choice_value'  => 'name',
                'choice_label'  => 'name'
            ]);
        $this->type->configureOptions($resolver);
    }

    public function testBuildForm(): void
    {
        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects(self::once())
            ->method('addModelTransformer');

        $this->type->buildForm($formBuilder, []);

        unset($formBuilder);
    }

    public function testGetParent(): void
    {
        self::assertEquals(Select2TranslatableEntityType::class, $this->type->getParent());
    }

    public function testGetName(): void
    {
        self::assertEquals('oro_email_system_template_list', $this->type->getName());
    }
}
