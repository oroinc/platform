<fieldset <?php echo $view['layout']->block($block, 'block_attributes') ?>>
    <legend><?php echo $view->escape($translatable ? $view['translator']->trans($title, $title_parameters, $translation_domain) : strtr($title, $title_parameters)) ?></legend>
    <?php echo $view['layout']->widget($block) ?>
</fieldset>
