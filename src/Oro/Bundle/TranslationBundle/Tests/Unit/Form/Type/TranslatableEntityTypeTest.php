<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Bundle\TranslationBundle\Tests\Unit\Form\Type\Stub\TestEntity;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\ChoiceList\Factory\DefaultChoiceListFactory;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class TranslatableEntityTypeTest extends FormIntegrationTestCase
{
    const TEST_CLASS      = 'TestClass';
    const TEST_IDENTIFIER = 'testId';
    const TEST_PROPERTY   = 'testProperty';

    /**
     * @var ClassMetadataInfo
     */
    protected $classMetadata;

    /**
     * @var Configuration
     */
    protected $ormConfiguration;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var EntityRepository
     */
    protected $entityRepository;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var Query|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $query;

    /**
     * @var TranslatableEntityType
     */
    protected $type;

    /**
     * @var array
     */
    protected $testChoices = array('one', 'two', 'three');

    /** @var MockObject */
    private $aclHelper;

    protected function setUp()
    {
        $this->classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->disableOriginalConstructor()
            ->getMock();
        $this->classMetadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue(self::TEST_IDENTIFIER));
        $this->classMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(array(self::TEST_IDENTIFIER)));
        $this->classMetadata->expects($this->any())
            ->method('getTypeOfField')
            ->will($this->returnValue('integer'));
        $this->classMetadata->expects($this->any())
            ->method('getName')
            ->will($this->returnValue(self::TEST_CLASS));

        $this->ormConfiguration = $this->getMockBuilder('Doctrine\ORM\Configuration')
            ->disableOriginalConstructor()
            ->setMethods(array('addCustomHydrationMode'))
            ->getMock();

        $locale = 'de_DE';

        $translatableListener = $this->createMock(TranslatableListener::class);
        $translatableListener->expects($this->any())
            ->method('getListenerLocale')
            ->willReturn($locale);

        $eventManager = $this->createMock(EventManager::class);
        $eventManager->expects($this->any())
            ->method('getListeners')
            ->willReturn([[$translatableListener]]);

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getClassMetadata', 'getConfiguration', 'getEventManager'])
            ->getMock();
        $this->entityManager->expects($this->any())
            ->method('getEventManager')
            ->willReturn($eventManager);
        $this->entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(self::TEST_CLASS)
            ->will($this->returnValue($this->classMetadata));
        $this->entityManager->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($this->ormConfiguration));

        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->setMethods(array('getManager', 'getRepository'))
            ->getMockForAbstractClass();
        $this->registry->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($this->entityManager));
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with(self::TEST_CLASS)
            ->will($this->returnValue($this->getEntityRepository()));

        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->type = new TranslatableEntityType($this->registry, new DefaultChoiceListFactory(), $this->aclHelper);

        parent::setUp();
    }

    protected function tearDown()
    {
        unset($this->classMetadata);
        unset($this->ormConfiguration);
        unset($this->entityManager);
        unset($this->registry);
        unset($this->entityRepository);
        unset($this->queryBuilder);
        unset($this->type);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return [
            new PreloadedExtension([$this->type], [])
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(TranslatableEntityType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->type->getParent());
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        $testChoiceEntities = $this->getTestChoiceEntities($this->testChoices);

        if (!$this->queryBuilder) {
            $this->query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
                ->disableOriginalConstructor()
                ->setMethods(array('execute', 'setHint', 'getSQL'))
                ->getMockForAbstractClass();
            $this->query->expects($this->any())
                ->method('execute')
                ->will($this->returnValue($testChoiceEntities));
            $this->query->expects($this->any())
                ->method('getSQL')
                ->will($this->returnValue('SQL QUERY'));

            $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
                ->disableOriginalConstructor()
                ->setMethods(array('getQuery', 'getParameters'))
                ->getMock();
            $this->queryBuilder->expects($this->any())
                ->method('getQuery')
                ->will($this->returnValue($this->query));
            $this->queryBuilder->expects($this->any())
                ->method('getParameters')
                ->will($this->returnValue(new ArrayCollection()));
        }

        return $this->queryBuilder;
    }

    /**
     * @return EntityRepository
     */
    public function getEntityRepository()
    {
        if (!$this->entityRepository) {
            $this->entityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
                ->disableOriginalConstructor()
                ->getMock();
            $this->entityRepository->expects($this->any())
                ->method('createQueryBuilder')
                ->with('e')
                ->will($this->returnValue($this->getQueryBuilder()));
        }

        return $this->entityRepository;
    }

    /**
     * @param  array $choices
     * @return array
     */
    protected function getTestChoiceEntities($choices)
    {
        foreach ($choices as $key => $value) {
            $entity = new TestEntity($key, $value);
            $choices[$key] = $entity;
        }

        return $choices;
    }

    /**
     * @param array $options
     * @param array $expectedCalls
     *
     * @dataProvider buildFormDataProvider
     */
    public function testBuildForm($options, array $expectedCalls = array())
    {
        /** @var FormBuilder|\PHPUnit\Framework\MockObject\MockObject $formBuilder */
        $formBuilder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('addEventSubscriber', 'addViewTransformer'))
            ->getMock();

        foreach ($expectedCalls as $method => $parameters) {
            $mocker = $formBuilder->expects($this->exactly($parameters['count']))
                ->method($method)
                ->will($this->returnSelf());
            call_user_func_array(array($mocker, 'with'), $parameters['with']);
        }

        // test
        $this->type->buildForm($formBuilder, $options);
    }

    /**
     * @return array
     */
    public function buildFormDataProvider()
    {
        return array(
            'single' => array(
                'options' => array(
                    'class'    => self::TEST_CLASS,
                    'multiple' => false,
                ),
            ),
            'multiple' => array(
                'options'       => array(
                    'class'    => self::TEST_CLASS,
                    'multiple' => true,
                ),
                'expectedCalls' => array(
                    'addEventSubscriber' => array(
                        'count' => 1,
                        'with'  => array(
                            $this->isInstanceOf(
                                'Symfony\Bridge\Doctrine\Form\EventListener\MergeDoctrineCollectionListener'
                            )
                        )
                    ),
                    'addViewTransformer' => array(
                        'count' => 1,
                        'with'  => array(
                            $this->isInstanceOf(
                                'Oro\Bundle\TranslationBundle\Form\DataTransformer\CollectionToArrayTransformer'
                            ),
                            true
                        )
                    ),
                ),
            ),
        );
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

        $this->assertArraySubset(
            [
                'class' => self::TEST_CLASS,
                'choice_value' => 'testId'
            ],
            $form->getConfig()->getOptions()
        );
    }
}
