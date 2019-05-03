<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\ImportExport\Strategy;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Strategy\AttributeImportStrategy;
use Oro\Bundle\EntityConfigBundle\ImportExport\Strategy\EntityFieldImportStrategy;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\GroupSequence;

class AttributeImportStrategyTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DatabaseHelper */
    protected $databaseHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FieldTypeProvider */
    protected $fieldTypeProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityFieldImportStrategy */
    protected $strategy;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FieldHelper */
    protected $fieldHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ImportStrategyHelper */
    protected $strategyHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FieldNameValidationHelper */
    protected $validationHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigHelper */
    protected $configHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContextInterface */
    protected $context;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
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

    public function testProcessValidationErrorsWithAttributesGroup()
    {
        $this->fieldTypeProvider->expects(static::any())
            ->method('getFieldProperties')
            ->willReturn([]);
        $this->translator->expects(static::any())
            ->method('trans')
            ->willReturnCallback(
                function ($value) {
                    return $value;
                }
            );
        $entity = new FieldConfigModel('testFieldName', 'integer');

        $this->fieldTypeProvider->expects($this->once())
            ->method('getSupportedFieldTypes')
            ->willReturn(['string', 'integer', 'date']);
        $this->configHelper->expects($this->once())
            ->method('addToFieldConfigModel')
            ->with($entity, ['attribute' => ['is_attribute' => true]]);

        $groups = ['FieldConfigModel', 'Sql', 'ChangeTypeField', 'AttributeField'];
        $this->strategyHelper->expects($this->once())
            ->method('validateEntity')
            ->with($entity, null, new GroupSequence($groups))
            ->willReturn(['first error message', 'second error message']);

        $this->context->expects($this->once())
            ->method('incrementErrorEntriesCount');
        $this->strategyHelper->expects($this->once())
            ->method('addValidationErrors')
            ->with(['first error message', 'second error message'], $this->context);

        self::assertNull($this->strategy->process($entity));
    }
}
