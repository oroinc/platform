<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ActivityBundle\Form\DataTransformer\ContextsToViewTransformer;
use Oro\Bundle\ActivityBundle\Form\Type\ContextsSelectType;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Form\Type\Select2HiddenType;
use Symfony\Component\Form\Test\TypeTestCase;

class ContextsSelectTypeTest extends TypeTestCase
{
    /** \PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /* @var \PHPUnit\Framework\MockObject\MockObject */
    protected $dispatcher;

    /* @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityTitleResolver;

    protected function setUp()
    {
        parent::setUp();
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\DataCollectorTranslator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityTitleResolver = $this->getMockBuilder(EntityNameResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testBuildForm()
    {
        $builder = $this->createMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('addViewTransformer');
        $type = new ContextsSelectType(
            $this->em,
            $this->configManager,
            $this->translator,
            $this->dispatcher,
            $this->entityTitleResolver,
            $this->createMock(FeatureChecker::class)
        );
        $type->buildForm(
            $builder,
            [
                'collectionModel' => false,
                'configs' => ['separator' => ContextsToViewTransformer::SEPARATOR]
            ]
        );
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'tooltip'         => false,
                    'collectionModel' => false,
                    'configs' => [
                        'placeholder'        => 'oro.activity.contexts.placeholder',
                        'allowClear'         => true,
                        'multiple'           => true,
                        'separator'          => ContextsToViewTransformer::SEPARATOR,
                        'forceSelectedData'  => true,
                        'minimumInputLength' => 0,
                    ]
                ]
            );

        $type = new ContextsSelectType(
            $this->em,
            $this->configManager,
            $this->translator,
            $this->dispatcher,
            $this->entityTitleResolver,
            $this->createMock(FeatureChecker::class)
        );
        $type->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $type = new ContextsSelectType(
            $this->em,
            $this->configManager,
            $this->translator,
            $this->dispatcher,
            $this->entityTitleResolver,
            $this->createMock(FeatureChecker::class)
        );
        $this->assertEquals(Select2HiddenType::class, $type->getParent());
    }

    public function testGetName()
    {
        $type = new ContextsSelectType(
            $this->em,
            $this->configManager,
            $this->translator,
            $this->dispatcher,
            $this->entityTitleResolver,
            $this->createMock(FeatureChecker::class)
        );
        $this->assertEquals('oro_activity_contexts_select', $type->getName());
    }
}
