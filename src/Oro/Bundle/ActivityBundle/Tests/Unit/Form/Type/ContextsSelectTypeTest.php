<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityBundle\Form\DataTransformer\ContextsToViewTransformer;
use Oro\Bundle\ActivityBundle\Form\Type\ContextsSelectType;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Form\Type\Select2HiddenType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\DataCollectorTranslator;

class ContextsSelectTypeTest extends TypeTestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private ConfigManager&MockObject $configManager;
    private DataCollectorTranslator&MockObject $translator;
    private EntityNameResolver&MockObject $entityTitleResolver;
    private ContextsSelectType $type;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->translator = $this->createMock(DataCollectorTranslator::class);
        $this->entityTitleResolver = $this->createMock(EntityNameResolver::class);

        $this->type = new ContextsSelectType(
            $this->doctrine,
            $this->configManager,
            $this->translator,
            $this->dispatcher,
            $this->entityTitleResolver,
            $this->createMock(FeatureChecker::class)
        );
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addViewTransformer');

        $this->type->buildForm(
            $builder,
            [
                'collectionModel' => false,
                'configs' => ['separator' => ContextsToViewTransformer::SEPARATOR]
            ]
        );
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
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

        $this->type->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(Select2HiddenType::class, $this->type->getParent());
    }
}
