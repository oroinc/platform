<?php //@codingStandardsIgnoreFile?>
<?php echo $view['form']->widget($form) ?>
<?php
    if ($render_rest) {
        echo $view['form']->rest($form);
    }
?>
