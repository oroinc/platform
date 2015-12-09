<?php //@codingStandardsIgnoreFile ?>
<?php if ($tag): ?>
    <<?php echo $tag ?> <?php echo $view['layout']->block($block, 'block_attributes') ?>>
    </<?php echo $tag ?>>
<?php endif ?>
