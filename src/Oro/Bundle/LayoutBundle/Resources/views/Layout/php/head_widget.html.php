<head <?php echo $view['block']->block($block, 'block_attributes') ?>>
    <title><?php echo $view['translator']->trans($title, array(), $translation_domain) ?></title>
    <?php echo $view['block']->widget($block) ?>
</head>
