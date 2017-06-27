<?php //@codingStandardsIgnoreFile?>
<?php
    if ($view['render_rest']) {
        echo $view['form']->end($form);
    } else {
        echo '</form>';
    }
?>
