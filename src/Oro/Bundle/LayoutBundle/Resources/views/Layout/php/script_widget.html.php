<?php //@codingStandardsIgnoreFile ?>
<?php if (!empty($attr['src'])): ?>
    <script <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?>></script>
<?php else: ?>
    <script <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?>>
        <?php echo $content ?>
    </script>
<?php endif ?>
