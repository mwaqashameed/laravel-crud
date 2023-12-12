<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class FormBuilder extends FormHTMLGenerator
{
    public $fieldArr = 
    [
        'type'=>'',
        'left_text'=>'',
        'right_text'=>'',
        'min'=>'',
        'max'=>'',
        'default'=>'',
        'place-holder'=>'',
        'class'=>''
    ];

    public $textArr = 
    [
        'type'=>'',
        'left_text'=>'',
        'right_text'=>'',
        'class'=>''
    ];
    public function __construct() {

    }

    //ModFieldBuild(['Price',['type'=>'text', 'left_text'=>'$'],'']);

    public function field($key,$label, $value, $fieldArr=['type'=>'text']) {
        
        return $this->ModFieldBuild([$label,$fieldArr,''], $key, $value);
    }

    /**  Important ************************************************
     * (Main Function) Display text with format.
     * @param array fieldArr etc ***** ['Price',['type'=>'text', 'left_text'=>'$'],'']
     * @param string key 
     * @param string val 
     * @param string fieldFunc 
     * @param string otherAttr 
     */
    public function ModFieldBuild($fieldArr, $key, $val, $fieldFunc = '', $otherAttr = '')
    { //main Func

        $this->fieldArr = 
    [
        'type'=>'',
        'left_text'=>'',
        'right_text'=>'',
        'min'=>'',
        'max'=>'',
        'default'=>'',
        'place-holder'=>'',
        'class'=>''
    ];

        $label = $fieldName = $this->label($fieldArr[0]);
        $type = '';
        if(is_array($fieldArr[1])) { // New Way Standard
            $type = $this->fieldArr['type'] = $fieldArr[1]['type'] ?? 'text';
            $this->fieldArr['left_text'] = $fieldArr[1]['left_text'] ?? '';
            $this->fieldArr['right_text'] = $fieldArr[1]['right_text'] ?? '';
            $this->fieldArr['min'] = $fieldArr[1]['min'] ?? '';
            $this->fieldArr['max'] = $fieldArr[1]['max'] ?? '';
            $this->fieldArr['place-holder'] = $fieldArr[1]['place-holder'] ?? $label;
            $this->fieldArr['default'] = $fieldArr[1]['default'] ?? '';
            if(!isset($fieldArr[1]['class']) || $fieldArr[1]['class']=='') {
                $this->fieldArr['class'] = 'form-control';
            }
        } else { //OLD style control field
            $type = $fieldArr[1];
            $this->fieldArr['class'] = 'form-control';
            $this->fieldArr['place-holder'] = $label;
        }

        $type = $this->autoFieldSettings($type);

        $fieldID = $fieldFunc . '_' . $key;

        $validationClassArr = array('phone' => 'vv_phone', 'email' => 'vv_email');
        $validationClass = '';
        if (isset($validationClassArr[$type])) {
            $validationClass = ' ' . $validationClassArr[$type];
        }

        //text, date, image, flag, email, password, phone, quantity,
        if (in_array($type,['','text', 'file', 'email', 'password', 'phone', 'number'])) {
            return $this->build($this->input($key, $fieldName, $val,'text'));
        }else if ($type == 'img' || substr($type, 0, 4) == 'img_') {
            return $this->build($this->input($key, $fieldName, $val,'file'));
        } else if ($type == 'flag') {
            return  $this->build($this->checkbox($key, $fieldName, $val,$type));
        } else if ($type == 'date') {
            return $this->build($this->date($key, $fieldName, $val,$type));
        } else if ($type == 'textarea') {
            return $this->build($this->textarea($key, $fieldName, $val));
         } 
        else if (substr($type, 0, 3) == 'dd_') { // Static Dropdown
            $arrD = $this->ModStaticArr($type, $fieldName);
            return $this->build($this->ModArr2DD($key, $arrD, $val, '', ''));
        } else if (substr($type, 0, 6) == 'rb_dd_') { //RB
            $arrD = $this->ModStaticArr(ltrim($type, 'rb_'), $fieldName);
            return $this->build($this->ModArr2RB($key, $arrD, $val, ''));
        } else if (substr($type, 0, 6) == 'cb_dd_') { //RB
            $arrD = $this->ModStaticArr(ltrim($type, 'cb_'), $fieldName);
            return $this->build($this->ModArr2CB($key, $arrD, $val, ''));
        } else if ($type == 'cb') {
            return $this->build($this->checkbox($key, $fieldName, $val,'text'));
        } else if (substr($type, 0, 2) == 'dd_') {
            $arrD = $this->ModStaticArr($type, $fieldName);
            return $this->ModArr2DD($key, $arrD, $val, '', '');
        } else if (substr($type, 0, 4) == 'dyn_') { //Generate Dynamic Drop from Database
            return $this->build($this->ModFieldBuildDynamic($fieldArr, $key, $val));
        } else {
            echo $type.' : No Condition found.'; exit;
            //return $this->build($this->input($key, $fieldName, $val,'text'));
        }
        
    } //end ModFieldBuild

    /**
     * (Main Function) Display text with format.
     *
     * This function display text in format.
     *
     * @param string $fieldKey to fetch in $dbRow.
     * @param object $dbRow complete all columns in current record.
     * @param array $settingArr complete all columns with current record.
     * @param array $fieldFormatDataArr format properties.
     *
     * @return string complete formatted text.
     *
     */

    public function text($fieldKey, $dbRow, $settingArr, $fieldFormatDataArr)
    {
        return $this->ModTextBuild($dbRow->$fieldKey, $fieldFormatDataArr[1], $settingArr['baseImg'], $dbRow, $fieldKey);
    }

    //Field Format .... on Listing Page.
    public function ModTextBuild($txt, $formatText = '', $baseImg = '', $rowObj = null, $dbKey = null)
    {
        $this->textArr = 
    [
        'type'=>'',
        'left_text'=>'',
        'right_text'=>'',
        'class'=>''
    ];

    $type = '';
    $format = '';
    if(is_array($formatText)) {
        $format = $type = $this->textArr['type'] = $formatText['type'] ?? '';
        $this->textArr['left_text'] = $formatText['left_text'] ?? '';
        $this->textArr['right_text'] = $formatText['right_text'] ?? '';
        $this->textArr['class'] = $formatText['class'] ?? '';
    } else { //OLD style control field
        $format = $type = $formatText;
    }

    $formatArr = explode('|', $format);
    $textAll = '';
    if(count($formatArr)>1){
        foreach ($formatArr as $kkFormat=>$valFormat) {
            $textAll .= $this->ModTextBuild($txt, $valFormat, $baseImg, $rowObj, $dbKey);
        }

        return $textAll;
    }
        if ($txt === '') {
            return '-';
        }
        if ($format == '' || $format == 'text') {
            return $this->textBuild(stripslashes($txt));
        } else if ($format == 'tick_cross' || $format == 'yes_no' || $format == 'flag') {
            return($txt=='1') ? $this->tag('span',$this->icon('tick'),'text-success') : $this->tag('span',$this->icon('cross'),'text-danger');
        } else if ($format == 'date' || $format == 'cdate' || $format == 'date_only') {
            return  the_date($this->textBuild($txt)); //'M. d, Y'
        } else if ($format == 'datetime') {
            return  $this->textBuild(the_date($txt, 'd M Y H:i A'));
        } else if ($format == 'format_sts') {
            if (in_array($txt, ['Recipe Delivered', 'File Delivered','Booking Submitted', 'Done'])) {
                 return $this->tag('span', $this->icon('tick') .' '.$txt, 'text-success');
            }
            if ($txt == 'Cancelled') {
                return $this->tag('span', $this->icon('cross') .' '.$txt, 'text-danger');
            }
            return '<span>' . $txt . '</span>';
        } else if ($format == 'price') {
            return $this->tag('code', $this->PRICE_SYMBOL . $txt);
        } else if ($format == 'price_short') {
            return $this->tag('code', AmountinShort($txt));
        } else if ($format == 'code') {
            return $this->tag('code', $txt);
        } else if ($format == 'str_study') {
            return Str::studly($txt);
        } else if ($format == 'color') {
            return '<span style="color:' . $rowObj->color . ';">' . $txt . '</span>';
        } else if ($format == 'price_code') {
        } else if (substr($format, 0, 13) == 'quick_revert_') {
            $fieldT = str_replace('quick_revert_', '', $format);
            return '<a  title="Click to Change" onClick="update_revert(\'' . $rowObj->id . '\',\'' . $fieldT . '\');" href="javascript:;">' . $txt . '</a>';
        } else if ($format == 'quick_edit') {
            $fieldT = $dbKey;
            $showTxt = ($txt == '' || $txt == null || trim($txt) == '') ? '...' : $txt;
            return '<a  title="Click to Change" onClick="get_field(\'' . $rowObj->id . '\',\'' . $fieldT . '\');" href="javascript:;">' . $showTxt  . '</a>';
        } else if (substr($format, 0, 11) == 'quick_edit_') {
            $fieldT = str_replace('quick_edit_', '', $format);
            $showTxt = ($txt == '' || $txt == null || trim($txt) == '') ? '...' : $txt;
            return '<a  title="Click to Change" onClick="get_field(\'' . $rowObj->id . '\',\'' . $fieldT . '\');" href="javascript:;">' . $showTxt  . '</a>';
        } else if ($format == 'price_code') {
            return  $this->textBuild($this->tag('code', $txt));
        } else if ($format == 'link') {
            return  $this->textBuild($this->link($txt,$txt,['newTab'=> true]));
        } else if ($format == 'link_site') {
            $link = $this->base_url() . $txt;
            return  $this->textBuild($this->link($txt,$txt,['newTab'=> true]));
        }  else if ($format == 'mailto' || $format == 'email') {
            return  $this->textBuild($this->link('mailto:' . $txt, $txt));
        } else if ($format == 'tel' || $format == 'phone') {
            return  $this->textBuild($this->link('tel:' . $txt, $txt));
        } else if ($format == 'img') {
            if ($txt == '') {
                return '-';
            }
            return '<img src="' . env('APP_URL').'/'.'front/user/' . $txt . '" >';
        } else if ($format == 'img_round')
            return '<img src="' . $this->base_url() . $baseImg . $txt . '" class="img-circle" >';

        else if ($format == 'img_pop')
            return '<img style="cursor:pointer;" onclick="displayImage(\'' . $this->base_url() . 'public/uploads/gallery/' . $txt . '\');" src="' . $this->base_url() . $baseImg . $txt . '" width="100" >';
        else if ($format == 'tick_cross') {
            return ($txt == '1') ? '<i class="fa fa-check text-success" aria-hidden="true"></i>' : '<i class="fa fa-times text-danger" aria-hidden="true"></i>';
        } else if ($format == 'e-msg')
            return  $this->textBuild($this->tag('div',$txt, 'alert-danger'));
        else if ($format == 's-msg')
            return  $this->textBuild($this->tag('div',$txt, 'alert-success'));
        else if ($format == 'w-msg')
            return  $this->textBuild($this->tag('div',$txt, 'alert-warning'));
        else if (substr($format, 0, 6) == 'limit_') {
            $limit = str_replace('limit_', '', $format);
            if (strlen($txt) >= $limit) {
                return $this->textBuild(substr($txt, 0, $limit) . '...');
            }
            return $txt;
        } else if (substr($format, 0, 6) == 'right_') {
            $rightText = str_replace('right_', '', $format);
            if ($txt == '') {
                return '';
            }
            return $this->textBuild($txt . $rightText);
        } else if (substr($format, 0, 5) == 'left_') {
            $leftText = str_replace('left_', '', $format);
            if ($txt == '') {
                return '';
            }
            return $this->textBuild($leftText . $txt);
        } else if (substr($format, 0, 3) == 'dd_') { //General Dropdown
            $arrD = $this->ModStaticArr($format, '');
            return $this->textBuild($arrD[$txt] ?? '');
        } else if (substr($format, 0, 4) == 'dyn_') {
           $testt = $this->ModFieldBuildDynamic(['', $format, ''], '', $txt, true);
            return $this->textBuild($this->ModFieldBuildDynamic(['', $format, ''], '', $txt, true));
        } else {
            return $this->textBuild(stripslashes($txt));
        }
    }

    /**
     * (Main Function) Make HTML Field like textbox,textarea etc.
     *
     * @param string $fieldKey to fetch in $dbRow.
     * @param object $dbRow complete all columns in current record.
     * @param array $settingArr complete all columns with current record.
     * @param array $fieldFormatDataArr format properties.
     *
     * @return string complete formatted text.
     *
     */

    public function formField($fType, $key, $label = '', $defaultValue = '', $validate = 'trim', $fieldFunc = '', $otherAttr = '')
    {
        $fieldArr = array($label, $fType, $validate);
        return $this->ModFieldBuild($fieldArr, $key, $defaultValue, $fieldFunc, $otherAttr);
    }

    //Create Textbox HTML
    public function ModTB($key, $label, $defaultValue = '', $validate = 'trim', $fieldFunc = '', $otherAttr = '')
    {
        $fieldArr = array($label, 'text', $validate);
        return $this->ModFieldBuild($fieldArr, $key, $defaultValue, $fieldFunc, $otherAttr);
    }

    //Create Textarea HTML
    public function ModTA($key, $label, $validate = 'trim', $defaultValue = '', $fieldFunc = '', $otherAttr = '')
    {
        $fieldArr = array($label, 'textarea', $validate);
        return $this->ModFieldBuild($fieldArr, $key, $defaultValue, $fieldFunc, $otherAttr);
    }


    public function ModFieldBuildDynamic($fieldArr, $key, $val, $single = false)
    {
        $type = $fieldArr[1];
        $fieldName = $this->ModAutoKeyToValue($fieldArr[0]);
        $dynArr = [
            'dyn_category' => ['App\Models\Back\Project', 'title', 'id', [], 'orderr', 'ASC'],
            'dyn_plot_size' => ['App\Models\Back\PlotSize', 'title', 'id', [], 'orderr', 'ASC'],
            'dyn_status' => ['App\Models\Back\Status', 'title', 'id', [], 'orderr', 'ASC'],
            'dyn_order_type' => ['App\Models\Back\OrderType', 'title', 'id', [], 'orderr', 'ASC'],
            'dyn_property_type' => ['App\Models\Back\PropertyType', 'title', 'id', [], 'orderr', 'ASC'],
            'dyn_user' => ['App\Models\Back\Client', 'name', 'id', [], 'id', 'DESC'],
            // 'dyn_status'=>['App\Models\Back\EmailTemplate', 'Title', 'ID',['email_type'=>'status'], 'ID','DESC'],
            'dyn_block' => ['App\Models\Back\Block', 'title', 'id', [], 'orderr', 'ASC'],
            'dyn_order_tot' => ['App\Models\Back\Order', 'user_id', $val, []],
            'dyn_client_image_tot' => ['App\Models\Back\ClientImage', 'client_id', $val, []],
            // 'dyn_lm'=>['App\Models\EmployeesModel','full_name','ID'],
        ];
        if (isset($dynArr[$type])) {
            if (substr($type, -4) == '_tot') {
                return $dynArr[$type][0]::where($dynArr[$type][3])->where($dynArr[$type][1], $val)->count();
            }
            if ($single == false) {
                $arr = $dynArr[$type][0]::where($dynArr[$type][3])->orderBy($dynArr[$type][4], $dynArr[$type][5])->pluck($dynArr[$type][1], $dynArr[$type][2])->toArray();
                return $this->ModArr2DD($key, $arr, $val, '', '- ' . $fieldName . ' -');
            } else {
                $val = $dynArr[$type][0]::where($dynArr[$type][3])->where($dynArr[$type][2], $val)->value($dynArr[$type][1]);
                return ($val == '') ? '--' : $val;
            }
        } else {
            echo $type.'::Dynmic Value not set mod file::'. get_class();
            exit;
        }
    }

    //Auto Field name with DB key(id)
    public function ModAutoKeyToValue($key)
    {
        $arrCommaon = array(
            'fname' => 'First Name',
            'lname' => 'Last Name',
            'id' => 'ID',
            'ID' => 'ID',
            'ip' => 'IP',
            'num' => 'Number',
            'orderr' => 'Order',
            'pass' => 'Password',
            'sts' => 'Status',
            'created_at' => 'Created Date',
            'updated_at' => 'Updated Date',
            'descp' => 'Description',
            'slug' => 'URL',
            'num' => 'Number',
            'userid' => 'ID',
            'cnic' => 'CNIC',
        );

        if (isset($arrCommaon[$key])) {
            return $arrCommaon[$key];
        } else {
            return Str::headline($key);
        }
    }

    public function ModStaticArr($type, $selectText = '')
    {
        $arr = array(
            'dd_export' => ['' => '-Export-', 'txt' => 'Export Text', 'csv' => 'Export CSV', 'xls' => 'Export Excel'],
            'dd_sts' => ['0' => 'No', '1' => 'Yes'],
            'dd_sts_assign_number' => array('Pending' => 'Pending', 'Assigned' => 'Assigned'),
            'dd_status_yn' => array('' => '-' . $this->label($selectText) . '-', '0' => 'Blocked', '1' => 'Active'),
            'dd_smedia' => array('' => '-' . $this->label($selectText) . '-', 'fa fa-facebook-square' => 'Facebook', 'fa fa-twitter-square' => 'Twitter', 'fa fa-google-plus-square' => 'Google+', 'fa fa-linkedin-square' => 'Linkedin', 'fa fa-pinterest-square' => 'Pinterest', 'fa fa-youtube-square' => 'Youtube', 'fa fa-stumbleupon' => 'Stumbleupon', 'fa fa-rss-square' => 'RSS'),
            'dd_alert_type' => array('w' => 'Warning', 'e' => 'Error', 's' => 'Success'),
            'dd_coupon_type' => array('P' => 'Percent', 'F' => 'Fixed'),
            'dd_month' =>  array(1 => 'Jan.', 2 => 'Feb.', 3 => 'Mar.', 4 => 'Apr.', 5 => 'May', 6 => 'Jun.', 7 => 'Jul.', 8 => 'Aug.', 9 => 'Sep.', 10 => 'Oct.', 11 => 'Nov.', 12 => 'Dec.'),
            'dd_date_day' => array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31),
            'dd_priority' => ['normal' => 'Normal', 'low' => 'Low', 'high' => 'High', 'critical' => 'Critical'],
            'dd_empty' => array()
        );

        if (!isset($arr[$type])) {
            echo $this->mod_error_show($type . ' create Array for "mod_builder_helper/ModStaticArr"');
            exit;
        }
        return $arr[$type];
    }

    public function ModArr2RB($name, $arr, $selectValue = '', $otherProperties = '')
    {
        if ($otherProperties == '') {
            $otherProperties = ' class="form-control"';
        }

        $tempVar = '';

        foreach ($arr as $key => $val) {
            $isChecked = '';

            if ($selectValue == $key) {
                $isChecked = 'checked';
            }

            $tempVar .= '<div class="form-check" ' . $otherProperties . '>
          <input type="radio" class="form-check-input" id="' . $name . '_' . $key . '" name="' . $name . '" value="' . $key . '" ' . $isChecked . '>
          <label class="form-check-label" for="check1">' . $val . '</label>
        </div>';
        }

        return $tempVar;
    }

    public function ModArr2CB($name, $arr, $selectValue = '', $otherProperties = '')
    {
        if ($otherProperties == '') {
            $otherProperties = ' class="form-control"';
        }

        $tempVar = '';

        foreach ($arr as $key => $val) {
            $isChecked = '';

            if ($selectValue == $key) {
                $isChecked = 'checked';
            }

            $tempVar .= '<div class="form-check" ' . $otherProperties . '>
          <input type="checkbox" class="form-check-input" id="' . $name . '_' . $key . '" name="' . $name . '" value="' . $key . '" ' . $isChecked . '>
          <label class="form-check-label" for="check1">' . $val . '</label>
        </div>';
        }

        return $tempVar;
    }

    public function ModArr2DD($name, $arr, $selectValue = '', $otherProperties = '', $selectText = '-Select-')
    {
        if ($otherProperties == '') {
            $otherProperties = ' class="form-control"';
        }
        if ($selectText == '') {
            $selectText = '-Select-';
        }
        $tempVar = '<select name="' . $name . '" id="' . $name . '" ' . $otherProperties . '>';
        $tempVar .= '<option value="">' . $selectText . '</option> ';
        foreach ($arr as $key => $val) {
            $tempVar .= '<option value="' . $key . '" ';
            if ($selectValue != '') {
                if ($selectValue == $key) {
                    $tempVar .= 'selected';
                }
            }

            $tempVar .= '>' . $val . '</option>';
        }

        $tempVar .= '</select>';
        return $tempVar;
    }

    public function buildFormRow($row,$dataArr)
    {
        if ($this->FORM_DISPLAY_TYPE == '1') {
            return $this->designColumn1($row,$dataArr);
        }
        else if($this->FORM_DISPLAY_TYPE == '2') {
            return $this->designColumn2($row,$dataArr);
        } else {
            echo $this->FORM_DISPLAY_TYPE. ' : Invalid Design Type';exit;
        }
    }

    public function designColumn1($row,$dataArr) {
        $rowHtml = '';
        foreach ($dataArr as $key => $val) {

            $label = $this->label($val[0]);
            $field =  $this->ModFieldBuild($val, $key, $row->$key ?? '', 'edit');
            $rowHtml .= <<<EOT
                    <div class="form-group row">
                    <label for="text" class="col-3 col-form-label text-right"> $label </label> 
                    <div class="col-6">
                        <div class="input-group">
                        $field
                        </div>
                    </div>
                    <div class="col-3"></div>
                    </div>
            EOT;
        }
        return $rowHtml;
    }

    public function designColumn2($row,$dataArr) {
        $rowHtml = '';
        foreach ($dataArr as $key => $val) {

            $label = $this->label($val[0]);
            $field =  $this->ModFieldBuild($val, $key, $row->$key ?? '', 'edit');
            $rowHtml .= <<<EOT
                    <div class="form-group row">
                    <label for="text" class="col-3 col-form-label text-right"> $label </label> 
                    <div class="col-6">
                        <div class="input-group">
                        $field
                        </div>
                    </div>
                    <div class="col-3"></div>
                    </div>
            EOT;
        }
        return $rowHtml;
    }

    public function icon($icon = 'add')
    {
        if ($icon == 'update') {
            $icon = 'edit';
        }
        $arr = array(
            'add' => 'plus-circle',
            'edit' => 'edit',
            'email' => 'envelope',
            'del' => 'minus-circle',
            'subm' => 'paper-plane',
            'info' => 'info-circle',
            'up' => 'arrow-up',
            'cross'=>'times',
            'tick'=>'check',
            'down' => 'arrow-down',
            'back' => 'angle-double-left',
            'star' => 'star',
            'start' => 'star',
            's' => 'search',
        );

        if (isset($arr[$icon])) {
            return '<i class="fa fa-' . $arr[$icon] . '" aria-hidden="true"></i>';;
        }
        return '<i class="fa fa-' . $icon . '" aria-hidden="true"></i>';
    }

    public function textBuild($text) {
        if ($this->textArr['left_text']!='' || $this->textArr['right_text']!='') {
            if($this->textArr['left_text']!='') {
                $text = $this->textArr['left_text'] . $text;
            }
            if($this->textArr['right_text']!='') {
                $text = $text . $this->textArr['right_text'];
            }

            return $text;
        }

        return $text;
    }

    public function autoFieldSettings($type) {
        if($type == 'price') {
            $this->fieldArr['left_text'] = $this->PRICE_SYMBOL;
            return 'text';
        }

        return $type;

    }
}
