<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Form\Type\RoleMultiSelectType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleMultiSelectTypeTest extends FormIntegrationTestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var RoleMultiSelectType */
    protected $formType;

    protected function setUp()
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $this->em = $this->createMock(EntityManager::class);
        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->with(Role::class)
            ->willReturn($metadata);

        $this->formType = new RoleMultiSelectType($this->em);

        parent::setUp();
    }

    public function testGetParent()
    {
        $this->assertEquals(OroJquerySelect2HiddenType::class, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_role_multiselect', $this->formType->getName());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'autocomplete_alias' => 'roles',
                    'configs' => [
                        'multiple' => true,
                        'width' => '400px',
                        'placeholder' => 'oro.user.form.choose_role',
                        'allowClear' => true,
                    ]
                ]
            );

        $this->formType->configureOptions($resolver);
    }

    public function testBuildView()
    {
        /** @var FormBuilder|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->with(new EntitiesToIdsTransformer($this->em, Role::class));

        $this->formType->buildForm($builder, ['entity_class' => Role::class]);
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param string|array $submittedData
     * @param array $expected
     */
    public function testSubmitEmptyData($submittedData, array $expected)
    {
        $form = $this->factory->create(RoleMultiSelectType::class, null, ['entity_class' => Role::class]);
        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertEquals($expected, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'empty string' => [
                'submittedData' => '',
                'expected' => [],
            ],
            'empty array' => [
                'submittedData' => [],
                'expected' => [],
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $searchHandler = $this->createMock(SearchHandlerInterface::class);
        $searchHandler->expects($this->any())
            ->method('getProperties')
            ->willReturn(['label']);

        /** @var SearchRegistry|\PHPUnit\Framework\MockObject\MockObject $searchRegistry */
        $searchRegistry = $this->createMock(SearchRegistry::class);
        $searchRegistry->expects($this->any())
            ->method('getSearchHandler')
            ->with('roles')
            ->willReturn($searchHandler);

        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    OroJquerySelect2HiddenType::class => new OroJquerySelect2HiddenType(
                        $this->em,
                        $searchRegistry,
                        $configProvider
                    )
                ],
                []
            ),
        ];
    }
}
