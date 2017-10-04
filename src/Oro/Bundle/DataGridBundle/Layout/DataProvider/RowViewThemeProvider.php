<?php

namespace Oro\Bundle\DataGridBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;

class RowViewThemeProvider
{
    /**
     * @var DataGridThemeHelper
     */
    protected $themeHelper;

    /**
     * @param DataGridThemeHelper $themeHelper
     */
    public function __construct(DataGridThemeHelper $themeHelper)
    {
        $this->themeHelper = $themeHelper;
    }

    /**
     * @param string $dataGridName
     *
     * @return null|string
     */
    public function getThemeByGridName($dataGridName)
    {
        return $this->themeHelper->getTheme($dataGridName);
    }
}
