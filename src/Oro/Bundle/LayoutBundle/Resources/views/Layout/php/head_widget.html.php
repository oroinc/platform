<head <?php echo $view['layout']->block($block, 'block_attributes') ?>>
    <title><?php echo $view->escape($view['translator']->trans($title, $title_parameters, $translation_domain)) ?></title>
    <?php echo $view['layout']->widget($block) ?>
</head>
