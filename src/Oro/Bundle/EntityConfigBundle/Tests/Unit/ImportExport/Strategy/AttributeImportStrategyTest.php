<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\ImportExport\Strategy;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Strategy\AttributeImportStrategy;
use Oro\Bundle\EntityConfigBundle\ImportExport\Strategy\EntityFieldImportStrategy;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Contracts\Translation\TranslatorInterface;

class AttributeImportStrategyTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DatabaseHelper */
    private $databaseHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FieldTypeProvider */
    private $fieldTypeProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityFieldImportStrategy */
    private $strategy;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FieldHelper */
    private $fieldHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ImportStrategyHelper */
    private $strategyHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FieldNameValidationHelper */
    private $validationHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigHelper */
    private $configHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContextInterface */
    private $context;

    protected function setUp(): void
    {
        $this->fieldTypeProvider = $this->createMock(FieldTypeProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->strategyHelper = $this->createMock(ImportStrategyHelper::class);
        $this->databaseHelper = $this->createMock(DatabaseHelper::class);
        $this->validationHelper = $this->createMock(FieldNameValidationHelper::class);
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->strategy = new AttributeImportStrategy(
            new EventDispatcher(),
            $this->strategyHelper,
            $this->fieldHelper,
            $this->databaseHelper
        );
        $this->context = $this->createMock(ContextInterface::class);

        $this->strategy->setImportExportContext($this->context);
        $this->strategy->setEntityName(FieldConfigModel::class);
        $this->strategy->setFieldTypeProvider($this->fieldTypeProvider);
        $this->strategy->setTranslator($this->translator);
        $this->strategy->setFieldValidationHelper($this->validationHelper);
        $this->strategy->setConfigHelper($this->configHelper);
    }

    /**
     * @dataProvider validationGroupsDataProvider
     */
    public function testProcessValidationErrorsWithAttributesGroup(bool $isNew, array $validationGroups)
    {
        $this->fieldTypeProvider->expects($this->any())
            ->method('getFieldProperties')
            ->willReturn([]);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);
        $entityModel = new EntityConfigModel(\stdClass::class);
        $entity = new FieldConfigModel('testFieldName', 'integer');
        $entity->setEntity($entityModel);
        $className = $entityModel->getClassName();
        $fieldName = $entity->getFieldName();

        $this->validationHelper->expects($this->once())
            ->method('findFieldConfig')
            ->with($className, $fieldName)
            ->willReturn($isNew ? null: new Config(new FieldConfigId('extend', $className, $fieldName), []));

        $this->fieldTypeProvider->expects($this->once())
            ->method('getSupportedFieldTypes')
            ->willReturn(['string', 'integer', 'date']);
        $this->configHelper->expects($this->once())
            ->method('addToFieldConfigModel')
            ->with($entity, ['attribute' => ['is_attribute' => true]]);

        $this->strategyHelper->expects($this->once())
            ->method('validateEntity')
            ->with($entity, null, new GroupSequence($validationGroups))
            ->willReturn(['first error message', 'second error message']);

        $this->context->expects($this->once())
            ->method('incrementErrorEntriesCount');
        $this->strategyHelper->expects($this->once())
            ->method('addValidationErrors')
            ->with(['first error message', 'second error message'], $this->context);

        self::assertNull($this->strategy->process($entity));
    }

    public function validationGroupsDataProvider(): array
    {
        return [
            'is new' => [
                true,
                ['FieldConfigModel', 'Sql', 'ChangeTypeField', 'AttributeField', 'UniqueField', 'UniqueMethod']
            ],
            'existing' => [
                false,
                ['FieldConfigModel', 'Sql', 'ChangeTypeField', 'AttributeField']
            ]
        ];
    }
}
