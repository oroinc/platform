<?php //@codingStandardsIgnoreFile ?>
<?php if (!empty($type)) $attr['type'] = $type; ?>
<?php if (!empty($start)) $attr['start'] = $start; ?>
<ol <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?>>
<?php foreach ($block as $child) : ?>
    <?php if ($child->vars['visible']): ?>
        <?php if (isset($child->vars['own_template']) && $child->vars['own_template']): ?><?php echo $view['layout']->widget($child) ?><?php else: ?><li><?php echo $view['layout']->widget($child) ?></li><?php endif ?>
    <?php endif ?>
<?php endforeach; ?>
</ol>
