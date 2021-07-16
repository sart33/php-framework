<form class="vg-wrap vg-element vg-ninteen-of-twenty" method="post" action="<?php echo $this->adminPath . $this->action; ?>"
      enctype="multipart/form-data">
    <div class="vg-wrap vg-element vg-full">
        <div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow">
            <div class="vg-element vg-half vg-left">
                <div class="vg-element vg-padding-in-px">
                    <input type="submit" class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button" value="Сохранить">
                </div>
                <?php if( empty($this->noDelete) && !empty($this->data)):?>
                    <div class="vg-element vg-padding-in-px">
                        <a href=""
                           class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button vg-center vg_delete">
                            <span>Удалить</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if(!empty($this->data)): ?>
    <input type="hidden" name="<?php echo $this->columns['id_row']?>" value="<?php $this->data[$this->columns['id_row']]?>">
        <?php endif; ?>

    <input type="hidden" name="table" value="<?php echo $this->table?>">

    <?php ?>
    <?php
    foreach ($this->blocks as $class => $block) {

        if(is_int($class)) $class = 'vg-rows';
        echo '<div class="vg-wrap vg-element ' . $class . '">';
        if ($class ==='vg-content') echo  '<div class="vg-full vg-firm-background-color4 vg-box-shadow">';
        if($class !== 'vg-content') echo '<div class="vg-full vg-firm-background-color4 vg-box-shadow">';

        if(!empty($block)) {

            foreach ($block as $row) {

                foreach ($this->templateArr as $template => $items) {
                    if(in_array($row, $items)) {
                        if(!@include $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php') {
                            throw new \core\base\exceptions\RouteException('template not found ' .
                                $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php');
                        }

                        break;
                    }
                }

            }
        }
//        if ($class ==='vg-content')

        if ($class !=='vg-content') echo '</div>';
        if ($class ==='vg-content') echo '</div>';
        echo '</div>';
        }
    ?>


    <div class="vg-wrap vg-element vg-full">
        <div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow">
            <div class="vg-element vg-half vg-left">
                <div class="vg-element vg-padding-in-px">
                    <input type="submit"
                           class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button"
                           value="Сохранить">
                </div>
                <?php if( empty($this->noDelete) && !empty($this->data)):?>
                    <div class="vg-element vg-padding-in-px">
                        <a href=""
                           class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button vg-center vg_delete">
                            <span>Удалить</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>

				