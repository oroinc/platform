<fieldset <?php echo $view['layout']->block($block, 'block_attributes') ?>>
    <legend><?php echo $view['translator']->trans($title, array(), $translation_domain) ?></legend>
    <?php echo $view['layout']->widget($block) ?>
</fieldset>
