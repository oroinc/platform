<?php //@codingStandardsIgnoreFile?>
<?php echo $view['layout']->widget($block) ?>
<?php
    if ($render_rest) {
        echo $view['form']->rest($form);
    }
?>
