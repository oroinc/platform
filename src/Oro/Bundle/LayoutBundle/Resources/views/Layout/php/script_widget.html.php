<?php //@codingStandardsIgnoreFile ?>
<?php if (!empty($attr['src'])): ?>
    <?php $attr['src'] = $view['assets']->getUrl($attr['src']); ?>
    <script <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?>></script>
<?php else: ?>
    <script <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?>>
        <?php echo $content ?>
    </script>
<?php endif ?>
