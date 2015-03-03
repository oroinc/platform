<head <?php echo $view['layout']->block($block, 'block_attributes') ?>>
    <title><?php echo $view->escape($translatable ? $view['translator']->trans($title, $title_parameters, $translation_domain) : strtr($title, $title_parameters)) ?></title>
    <?php echo $view['layout']->widget($block) ?>
</head>
