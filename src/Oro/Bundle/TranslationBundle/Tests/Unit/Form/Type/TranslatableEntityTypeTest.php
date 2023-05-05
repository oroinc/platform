<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventManager;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TranslationBundle\Form\DataTransformer\CollectionToArrayTransformer;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Bundle\TranslationBundle\Tests\Unit\Form\Type\Stub\TestEntity;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\EventListener\MergeDoctrineCollectionListener;
use Symfony\Component\Form\ChoiceList\Factory\DefaultChoiceListFactory;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class TranslatableEntityTypeTest extends FormIntegrationTestCase
{
    private const TEST_CLASS = 'TestClass';
    private const TEST_IDENTIFIER = 'testId';
    private const TEST_PROPERTY = 'testProperty';

    /** @var EntityRepository */
    private $entityRepository;

    /** @var QueryBuilder */
    private $queryBuilder;

    /** @var Query|\PHPUnit\Framework\MockObject\MockObject */
    private $query;

    /** @var TranslatableEntityType */
    private $type;

    /** @var array */
    private $testChoices = ['one', 'two', 'three'];

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    protected function setUp(): void
    {
        $classMetadata = $this->createMock(ClassMetadataInfo::class);
        $classMetadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn(self::TEST_IDENTIFIER);
        $classMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn([self::TEST_IDENTIFIER]);
        $classMetadata->expects($this->any())
            ->method('getTypeOfField')
            ->willReturn('integer');
        $classMetadata->expects($this->any())
            ->method('getName')
            ->willReturn(self::TEST_CLASS);

        $locale = 'de_DE';

        $translatableListener = $this->createMock(TranslatableListener::class);
        $translatableListener->expects($this->any())
            ->method('getListenerLocale')
            ->willReturn($locale);

        $eventManager = $this->createMock(EventManager::class);
        $eventManager->expects($this->any())
            ->method('getListeners')
            ->willReturn([[$translatableListener]]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->any())
            ->method('getEventManager')
            ->willReturn($eventManager);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(self::TEST_CLASS)
            ->willReturn($classMetadata);
        $entityManager->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($this->createMock(Configuration::class));

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($entityManager);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->with(self::TEST_CLASS)
            ->willReturn($this->getEntityRepository());

        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->type = new TranslatableEntityType($doctrine, new DefaultChoiceListFactory(), $this->aclHelper);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->type], [])
        ];
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('translatable_entity', $this->type->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->type->getParent());
    }

    private function getQueryBuilder(): QueryBuilder
    {
        $testChoiceEntities = $this->getTestChoiceEntities($this->testChoices);

        if (!$this->queryBuilder) {
            $this->query = $this->createMock(AbstractQuery::class);
            $this->query->expects($this->any())
                ->method('execute')
                ->willReturn($testChoiceEntities);
            $this->query->expects($this->any())
                ->method('getSQL')
                ->willReturn('SQL QUERY');

            $this->queryBuilder = $this->createMock(QueryBuilder::class);
            $this->queryBuilder->expects($this->any())
                ->method('getQuery')
                ->willReturn($this->query);
            $this->queryBuilder->expects($this->any())
                ->method('getParameters')
                ->willReturn(new ArrayCollection());
        }

        return $this->queryBuilder;
    }

    private function getEntityRepository(): EntityRepository
    {
        if (!$this->entityRepository) {
            $this->entityRepository = $this->createMock(EntityRepository::class);
            $this->entityRepository->expects($this->any())
                ->method('createQueryBuilder')
                ->with('e')
                ->willReturn($this->getQueryBuilder());
        }

        return $this->entityRepository;
    }

    private function getTestChoiceEntities(array $choices): array
    {
        foreach ($choices as $key => $value) {
            $entity = new TestEntity($key, $value);
            $choices[$key] = $entity;
        }

        return $choices;
    }

    /**
     * @dataProvider buildFormDataProvider
     */
    public function testBuildForm(array $options, array $expectedCalls = [])
    {
        $formBuilder = $this->createMock(FormBuilder::class);

        foreach ($expectedCalls as $method => $parameters) {
            $formBuilder->expects($this->exactly($parameters['count']))
                ->method($method)
                ->with(...$parameters['with'])
                ->willReturnSelf();
        }

        $this->type->buildForm($formBuilder, $options);
    }

    public function buildFormDataProvider(): array
    {
        return [
            'single' => [
                'options' => [
                    'class'    => self::TEST_CLASS,
                    'multiple' => false,
                ],
            ],
            'multiple' => [
                'options'       => [
                    'class'    => self::TEST_CLASS,
                    'multiple' => true,
                ],
                'expectedCalls' => [
                    'addEventSubscriber' => [
                        'count' => 1,
                        'with'  => [
                            $this->isInstanceOf(
                                MergeDoctrineCollectionListener::class
                            )
                        ]
                    ],
                    'addViewTransformer' => [
                        'count' => 1,
                        'with'  => [
                            $this->isInstanceOf(
                                CollectionToArrayTransformer::class
                            ),
                            true
                        ]
                    ],
                ],
            ],
        ];
    }

    public function testBuildViewWhenChoicesGiven()
    {
        $customChoice = new TestEntity('0', 'someValue');
        $form = $this->factory->create(TranslatableEntityType::class, null, [
            'class' => self::TEST_CLASS,
            'choice_label' => self::TEST_PROPERTY,
            'choices' => [$customChoice]
        ]);

        $formView = $form->createView();

        $this->assertEquals(
            [new ChoiceView($customChoice, '0', 'someValue')],
            $formView->vars['choices']
        );
    }

    public function testBuildViewWhenNoChoicesGiven()
    {
        $form = $this->factory->create(TranslatableEntityType::class, null, [
            'class' => self::TEST_CLASS,
            'choice_label' => self::TEST_PROPERTY,
        ]);

        $formView = $form->createView();

        $this->assertEquals(
            [
                new ChoiceView(new TestEntity('0', 'one'), '0', 'one'),
                new ChoiceView(new TestEntity('1', 'two'), '1', 'two'),
                new ChoiceView(new TestEntity('2', 'three'), '2', 'three')
            ],
            $formView->vars['choices']
        );
    }

    public function testBuildViewWhenQueryBuilderGiven()
    {
        $form = $this->factory->create(TranslatableEntityType::class, null, [
            'class' => self::TEST_CLASS,
            'choice_label' => self::TEST_PROPERTY,
            'query_builder' => $this->getQueryBuilder()
        ]);

        $formView = $form->createView();

        $this->assertEquals(
            [
                new ChoiceView(new TestEntity('0', 'one'), '0', 'one'),
                new ChoiceView(new TestEntity('1', 'two'), '1', 'two'),
                new ChoiceView(new TestEntity('2', 'three'), '2', 'three')
            ],
            $formView->vars['choices']
        );
    }

    public function testBuildViewWhenQueryBuilderCallbackGiven()
    {
        $form = $this->factory->create(TranslatableEntityType::class, null, [
            'class' => self::TEST_CLASS,
            'choice_label' => self::TEST_PROPERTY,
            'query_builder' => function (EntityRepository $entityRepository) {
                $this->assertEquals($this->getEntityRepository(), $entityRepository);

                return $this->getQueryBuilder();
            }
        ]);

        $formView = $form->createView();

        $this->assertEquals(
            [
                new ChoiceView(new TestEntity('0', 'one'), '0', 'one'),
                new ChoiceView(new TestEntity('1', 'two'), '1', 'two'),
                new ChoiceView(new TestEntity('2', 'three'), '2', 'three')
            ],
            $formView->vars['choices']
        );
    }

    public function testConfigureOptionsWhenClassOptionIsNotSet()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "class" is missing.');

        $this->factory->create(TranslatableEntityType::class, null, []);
    }

    public function testConfigureOptions()
    {
        $form = $this->factory->create(TranslatableEntityType::class, null, ['class' => self::TEST_CLASS]);

        $options = $form->getConfig()->getOptions();

        $this->assertSame(self::TEST_CLASS, $options['class']);
        $this->assertSame('testId', $options['choice_value']);
    }
}
