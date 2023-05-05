<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Form\Type\IntegrationSelectType;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegrationSelectTypeTest extends OrmTestCase
{
    /** @var TypesRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var Packages|\PHPUnit\Framework\MockObject\MockObject */
    private $assetHelper;

    /** @var IntegrationSelectType */
    private $type;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $this->registry = $this->createMock(TypesRegistry::class);
        $this->assetHelper = $this->createMock(Packages::class);
        $aclHelper = $this->createMock(AclHelper::class);

        $this->type = new IntegrationSelectType(
            $this->em,
            $this->registry,
            $this->assetHelper,
            $aclHelper
        );
    }

    public function testName()
    {
        $this->assertSame('oro_integration_select', $this->type->getName());
    }

    public function testParent()
    {
        $this->assertSame(Select2ChoiceType::class, $this->type->getParent());
    }

    public function testFinishView()
    {
        $this->registry->expects($this->once())
            ->method('getAvailableIntegrationTypesDetailedData')
            ->willReturn(
                [
                    'testType1' => ['label' => 'oro.type1.label', 'icon' => 'bundles/acmedemo/img/logo.png'],
                    'testType2' => ['label' => 'oro.type2.label'],
                ]
            );

        $this->assetHelper->expects($this->once())
            ->method('getUrl')
            ->willReturnArgument(0);

        $testIntegration1 = new Integration();
        $testIntegration1->setType('testType1');
        $testIntegration1Label = uniqid('label');
        $testIntegration1Id = uniqid('id');

        $testIntegration2 = new Integration();
        $testIntegration2->setType('testType2');
        $testIntegration2Label = uniqid('label');
        $testIntegration2Id = uniqid('id');

        $view = new FormView();
        $view->vars['choices'] = [
            new ChoiceView($testIntegration1, $testIntegration1Id, $testIntegration1Label),
            new ChoiceView($testIntegration2, $testIntegration2Id, $testIntegration2Label),
        ];

        $this->type->finishView($view, $this->createMock(FormInterface::class), []);

        $this->assertEquals($testIntegration1Label, $view->vars['choices'][0]->label);
        $this->assertEquals($testIntegration2Label, $view->vars['choices'][1]->label);

        $this->assertEquals(
            [
                'data-status' => true,
                'data-icon'   => 'bundles/acmedemo/img/logo.png'
            ],
            $view->vars['choices'][0]->attr
        );
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->type->configureOptions($resolver);

        $resolved = $resolver->resolve(
            [
                'configs'       => ['placeholder' => 'testPlaceholder'],
                'allowed_types' => ['testType']
            ]
        );
        $this->assertInstanceOf(ChoiceLoaderInterface::class, $resolved['choice_loader']);
    }
}
