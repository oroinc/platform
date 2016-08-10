<?php //@codingStandardsIgnoreFile ?>
<ol <?php echo $view['layout']->block($block, 'block_attributes') ?><?php if (isset($type)): ?> type="<?php echo $type ?>"<?php endif ?><?php if (isset($start)): ?> start="<?php echo $start ?>"<?php endif ?>>
<?php foreach ($block as $child) : ?>
    <?php if ($child->vars['visible']): ?>
        <?php if (isset($child->vars['own_template']) && $child->vars['own_template']): ?><?php echo $view['layout']->widget($child) ?><?php else: ?><li><?php echo $view['layout']->widget($child) ?></li><?php endif ?>
    <?php endif ?>
<?php endforeach; ?>
</ol>
