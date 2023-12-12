<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class FormHTMLGenerator
{

    public function input($key, $label, $val = '',$type='text')
    {
        $formHtml = "<input ".
        $this->tagProp('type',$this->fieldArr['type'])
        .$this->tagProp('class',$this->fieldArr['class'])
        .$this->tagProp('placeholder',$this->fieldArr['place-holder']);
        
        if(in_array($this->fieldArr['type'],['number', 'range'])){
            $formHtml .= $this->tagProp('min',(int)$this->fieldArr['min']);
            if($this->fieldArr['min']!='') {
                $formHtml .= $this->tagProp('max',(int)$this->fieldArr['max']);
            }
        }
       
        $formHtml .=" name='$key' id='$key' value='".htmlspecialchars($val)."'>";

        return $formHtml;
    }
    

    public function textarea($key, $label, $val = '',$type='text')
    {
        $img = '<textarea class="form-control" name="' . $key . '" id="' . $key . '">' . htmlspecialchars($val) . '</textarea>';
            return $img;
    }

    public function date($key, $label, $val = '',$type='text')
    {
        if ($key == 'created_at') {
            $val = date('Y-m-d');
        }
        $img = '<input  onfocus="(this.type=\'date\')" type="text" class="form-control date_cal" placeholder="' . $this->ModAutoKeyToValue($label) . '" name="' . $key . '" id="' . $key . '" value="' . htmlspecialchars($val) . '" />';
        return $img;
    }
    
    public function slide_yes_no($key, $label, $val = '',$type='')
    {
        $retData = '';
        $tstArr = explode('__', $type);
        $isChecked = '';
        if ($val == '1') {
            $isChecked = 'checked';
        }
        $retData = '<input type="checkbox" ' . $isChecked . '  data-toggle="toggle_ajax" data-onstyle="success" data-offstyle="danger" data-on="' . $tstArr[1] . '" data-off="' . $tstArr[2] . '"  name="' . $key . '" data-size="mini" id="' . $key . '" onChange="updatePageStatus(this.checked,\'idddd\',\'' . $key . '\')"> ';
        return $retData;
    }

    public function radio($key, $label, $val = '',$type='text')
    {
        $checkedOrNOt = '';
        if ($val == '1') {
            $checkedOrNOt = 'checked';
        }
        $html = '<input ' . $checkedOrNOt . ' class="form-control"  type="radio" name="' . $key . '" id="' . $key . '" placeholder="Yes" />';
        return $html;
    }

    public function checkbox($key, $label, $val = '',$type='text')
    {

        $checkedOrNOt = '';
        if ($val == '1') {
            $checkedOrNOt = 'checked';
        }
        $html = '<input ' . $checkedOrNOt . ' class="form-control"  type="checkbox" name="' . $key . '" id="' . $key . '" placeholder="Yes" />';
        return $html;
    }

    public function build($formHtml) {
        if ($this->fieldArr['left_text']!='' || $this->fieldArr['right_text']!='') {
            $html = '<div class="input-group mb-3">';
            if($this->fieldArr['left_text']!='') {
                $html .= '<span class="input-group-text" id="basic-addon2">' . $this->fieldArr['left_text'] . '</span>';
            }
            $html .= $formHtml;
            if($this->fieldArr['right_text']!='') {
                $html .= '<span class="input-group-text" id="basic-addon2">' . $this->fieldArr['right_text'] . '</span>';
            }
            $html .= '</div>';

            return $html;
        }

        return $formHtml;
    }

    public function submit($label='Submit')
    {
        return "<input type='submit' class='form-control' value='$label'>";
    }

    /**
     * Create HTML Tag.
     *
     * This function create html tag base.
     *
     * @param string $htmlTag html tag like p,span,div.
     * @param string $text in tag.
     * @param string $class of tag.
     *
     * @return string complete html tag.
     */

    public function tag($htmlTag,$text, $tagClass='') {
        return '<'.$htmlTag.' class="'.$tagClass.'">'.$text.'</'.$htmlTag.'>';
    }

    public function tagProp($tagProperty,$value='', $withFlag=true) {
        if($withFlag==false) {return '';}
        return ' '.$tagProperty.'="'.$value.'"';
    }

    /**
     * Create HTML Link Tag.
     *
     * This function takes two numbers as input and returns their sum.
     *
     * @param string $link 
     * @param string $text on link.
     * @param array $otherProperties. [class=>'text-success', newTab=>true]
     *
     * @return string complete html tag.
     */
    public function link($link, $text,$otherProperties=[]) {
        $class = $this->tagProp('class',$otherProperties['class'] ?? '');
        $newTab = $otherProperties['newTab'] ?? false; // Default is false

        return '<a '.$this->tagProp('target','_blank',$newTab).' href="'.$link.'" class="'.$class.'">'.$text.'</a>';
    }
}
