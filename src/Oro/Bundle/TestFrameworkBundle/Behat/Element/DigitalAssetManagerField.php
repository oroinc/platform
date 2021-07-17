<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Testwork\Suite\Suite;

/**
 * Behat Element to upload the file via DAM to a form
 */
class DigitalAssetManagerField extends Element implements SuiteAwareInterface
{
    /** @var Suite */
    private $suite;

    /**
     * {@inheritdoc}
     */
    public function setSuite(Suite $suite): void
    {
        $this->suite = $suite;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($filename): void
    {
        $this->open();

        if ($this->hasRow($filename)) {
            $this->chooseRow($filename);
        } else {
            $this->upload($filename);

            $this->chooseRow($filename);
        }
    }

    /**
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    public function upload(string $filename): void
    {
        $digitalAssetInDialogForm = $this->elementFactory->createElement('Digital Asset Dialog Form');
        $digitalAssetInDialogForm->fillField($digitalAssetInDialogForm->getOption('mapping')['Title'], $filename);
        $digitalAssetInDialogForm->fillField($digitalAssetInDialogForm->getOption('mapping')['File'], $filename);
        $digitalAssetInDialogForm->clickOrPress('Upload');

        $this->getDriver()->waitForAjax();
    }

    public function open(): void
    {
        $chooseButton = $this->elementFactory->createElement('Digital Asset Choose');
        $this->assertTrue($chooseButton->isValid(), 'Choose button is not found');

        $chooseButton->click();

        $this->getDriver()->waitForAjax();
    }

    public function hasRow(string $rowContent): bool
    {
        $grid = $this->getGrid();

        $gridRow = $grid->getRowByContent($rowContent, false);

        return $gridRow && $gridRow->isValid();
    }

    private function getGrid(): Table
    {
        /** @var Table $grid */
        $grid = $this->elementFactory->createElement('Digital Asset Select Grid');
        $this->assertTrue($grid->isValid(), 'Digital Asset Select Grid is not found');

        return $grid;
    }

    public function chooseRow(string $rowContent): void
    {
        $grid = $this->getGrid();

        $gridRow = $grid->getRowByContent($rowContent, false);

        $result = $this->spin(function () use ($gridRow) {
            $gridRow->click();
            return true;
        });

        $this->assertTrue($result, sprintf('Failed to click on grid row %s', $rowContent));
    }
}
