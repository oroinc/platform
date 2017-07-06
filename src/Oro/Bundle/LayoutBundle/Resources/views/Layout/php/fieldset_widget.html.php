<?php //@codingStandardsIgnoreFile?>
<fieldset <?php echo $view['layout']->block($block, 'block_attributes') ?>>
    <legend><?php echo $view->escape($view['layout']->text($title, $translation_domain)) ?></legend>
    <?php echo $view['layout']->widget($block) ?>
</fieldset>
