<head <?php echo $view['layout']->block($block, 'block_attributes') ?>>
    <title><?php echo $view['translator']->trans($title, array(), $translation_domain) ?></title>
    <?php echo $view['layout']->widget($block) ?>
</head>
