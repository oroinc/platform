<?php //@codingStandardsIgnoreFile ?>
<head <?php echo $view['layout']->block($block, 'block_attributes') ?>>
    <title><?php echo $view->escape($view['layout']->text($title, $translation_domain)) ?></title>
    <?php echo $view['layout']->widget($block) ?>
</head>
