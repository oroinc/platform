<fieldset <?php echo $view['layout']->block($block, 'block_attributes') ?>>
    <legend><?php echo $view->escape($view['translator']->trans($title, $title_parameters, $translation_domain)) ?></legend>
    <?php echo $view['layout']->widget($block) ?>
</fieldset>
