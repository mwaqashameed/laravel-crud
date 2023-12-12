<?php

namespace FastCodings;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
// >>>>>>>>>>>>>>>>>> Start Mod Class >>>
/**
 * Class for generating forms for  a given.
 */

class Mod extends FormBuilder
{
    public  $adminBaseText = 'admin-panel'; 
    public $PRICE_SYMBOL = 'Rs.';
    public $FORM_DISPLAY_TYPE = '1'; // 1 filed column or 2

    public $request;
    public $isAjax=true; /// true or false
    public $settingArr; /// Module Settings array

    public function __construct($settingArr=[])
    {
        $this->settingArr = $settingArr;
    }

    public function get($key)
    {
        $shortFormArr = [
            'key'=>'contr_name',
            'title'=>'mainPageTitle',
            'stitle'=>'mainTitle',
            'add'=>'add_func',
            'edit'=>'edit_func',
            'delete'=>'delete_func',
            'detail'=>'detail_func',
            'order'=>'order_func'
        ];
        $key = $shortFormArr[$key] ?? $key;
        
        if (isset($this->settingArr[$key])) {
            return $this->settingArr[$key];
        }

        echo $key . ': Not existed';exit;
    }

    public function settings()
    {
        return $this->settingArr;
    }

    //Build form fields dynamically
    /**
     * @param string 'key'  id and name of the field
     * @param array 'formatTextArr' contains the format type of the field
     * @param string formType create OR edit
     * @return boolean
     */
    // Sample Data 
    //Mod2Filed('email',['Email','','']])
    public function Mod2Filed($key, $formatTextArr, $formType = 'create')
    {
        return $this->ModFieldBuild($formatTextArr, $key, $formatTextArr[3]['default'] ?? '', $formType);
    }

    public function Mod2Text($fieldKey, $dbRow, $settingArr, $fieldFormatDataArr)
    {
        return $this->ModTextBuild($dbRow->$fieldKey, $fieldFormatDataArr[1], $settingArr['baseImg'], $dbRow, $fieldKey);
    }
    //BreadCrumbs
    public function ModBC($heading, $parentArr = array())
    {
        $tmpStr = '';
        $tmpStr .= '<ol class="breadcrumb modBC"><li class="breadcrumb-item"><a href="' . $this->admin_url() . 'dashboard' . '"><i class="fa fa-dashboard"></i> Dashboard</a>
                                        </li>';
        if (!empty($parentArr)) {
            foreach ($parentArr as $key => $val) {
                $tmpStr .= '<li class="breadcrumb-item"><a href="' . $this->admin_url() . $key . '">' . $val . '</a></li>';
            }
        }
        $tmpStr .= '<li class="breadcrumb-item active">' . $heading . '</li></ol><div class="clear"></div>';

        return $tmpStr;
    }

    public function setRequest($request){
        $this->request = $request;
        $this->isAjax = $this->request->ajax();
    }

    public function getRequest(){
        return $this->request;
    }

    public function formField($fType, $key, $label = '', $defaultValue = '', $validate = 'trim', $fieldFunc = '', $otherAttr = '')
    {
        $fieldArr = array($label, $fType, $validate);
        return $this->ModFieldBuild($fieldArr, $key, $defaultValue, $fieldFunc, $otherAttr);
    }

    //Auto Complete HTML Build
    public function ModArrList($id, $arr)
    {
        $str = '<datalist id="' . $id . '">';
        foreach ($arr as $key => $val) {
            $str .= '<option value="' . $val . '">';
        }
        $str .= '</datalist>';
        return $str;
    }

    //Create Add/Edit Array for auto add/edit.
    public function Mod_create_process_arr($row, $default_val = 'trim')
    {
        foreach ($row as $key => $val) {
            echo "'" . $key . "'=>array('" . $this->ModAutoKeyToValue($key) . "','text','" . $default_val . "'),";
            echo '<br/>';
        }
    }

    // validation step 1 *** ModConvertValidationArr
    public function checkValidation($arr, $id = '')
    {
        $validationArr = $this->requestToValidationArray($arr);
        $validatord = Validator::make(
            $this->request->all(),
            $validationArr[0],
            $validationArr[1]

        );

        if ($validatord->fails()) {
            if ($this->request->ajax()) {
                echo json_encode($validatord->errors());
                exit;
            } else {
                return back()
                    ->withErrors($validatord)
                    ->withInput();
            }
        }
    }
    public function requestToValidationArray($arr, $id = '')
    {
        $validationArr = array(
            array(),
            array()
        );
        

        $tmpArr1 = array();
        $tmpArr2 = array();
        foreach ($arr as $key => $value) {
            $value[2] = str_replace('{id}', $id, $value[2]);
            $type = $this->getFieldType($value);
            if ($type == 'img') {
                $tmpArr1[$key] = 'image|mimes:jpeg,png,jpg|max:2048';
            } else if ($value[2] == '' && $type != 'cb' && stristr($type, 'slide_flag__') == false) {
                $tmpArr1[$key] = 'required';
            } else {
                $tmpArr1[$key] = $value[2];
            }

            $tmpArr2[$key . '.required'] = $this->ModAutoKeyToValue($value[0]) . ' field is required.';
        }
        return array($tmpArr1, $tmpArr2);
    }

    // validation step 2 *** insert in db  ** ModConvertDBValue
    public function requestToDbArray($request, $kk, $vv)
    {

        $type = $this->getFieldType($vv);

        if(is_array($vv[1])) {
            $type = $vv[1]['type'] ?? 'text';
        } else{
            $type = $vv[1];
        }

        if ( $type == 'cb' || stristr( $type, 'slide_flag__')) {
            if ($request->has($kk)) {
                return '1';
            } {
                return '0';
            }
        }  else {
            return $request->$kk;
        }
    }

    public function viewtxt($keyy, $searchDataArr)
    {
        if (isset($searchDataArr[$keyy]) && $searchDataArr[$keyy] != '') {
            return stripslashes($searchDataArr[$keyy]);
        }
        return '';
    }

    public function filter_search_param($get, $searchArr)
    {
        $retArr = [];
        foreach ($searchArr as $key => $value) {
            if (isset($get[$key]) && $get[$key] != '') {
                $retArr[$key] = trim($get[$key]);
            }
        }

        return $retArr;
    }

    public function mod_error_show($err = '')
    {
        return '<div class="alert alert-danger">ERROR:: ' . $err . '</div>';
    }

    public function response($resp = true, $errorMsg = '', $otherResponseArr = [])
    {
        $successText = 'done';
        if ($resp != true) {
            $successText = 'error';
        }
        $respArr = ['success' => $successText, 'errormsg' => $errorMsg];
        foreach ($otherResponseArr as $kk => $val) {
            $respArr[$kk] = $val;
        }
        echo json_encode($respArr);
        exit;
    }

    public function base_url($settingArr)
    {
        return  env('APP_URL').'/';
    }
    
    public function mod_url($settingArr)
    {
        return $this->admin_url() . $settingArr['contr_name'] . '/';
    }
    
    public function admin_base()
    {
        return $this->adminBaseText;
    }

    public function admin_url() {
        return env('APP_URL').'/'.$this->adminBaseText.'/';
    }

    public function mod_upload_image($request, $postName = 'img', $defaultPath = 'front/user/')
    {
        $imgURL = '';
        $target_name = '';
        if (isset($_FILES['img']['name']) && $_FILES[$postName]['name'] != '') {
            $allowed =  array('gif', 'png', 'jpg');
            $filename = $_FILES[$postName]['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $target_name = time() . rand(1111, 9999) . '-' . $filename;
            $target_file = $defaultPath .  $target_name;
            if (!in_array($ext, $allowed)) {
                echo 'Please upload valid image';
                exit;
            }

            $check = getimagesize($_FILES[$postName]["tmp_name"]);
            if ($check !== false) {
            } else {
                echo 'Please upload valid image';
                exit;
            }

            if (move_uploaded_file($_FILES[$postName]["tmp_name"], $target_file)) {
            }
        }

        return $target_name;
    }

    public function getFieldType($fieldFormatArr) {

        $type = '';
        if(is_array($fieldFormatArr[1])) {
            $type = $fieldFormatArr[1]['type'] ?? 'text';
        } else{
            $type = $fieldFormatArr[1];
        }
    }

    public function label($key)
    {
        return $this->ModAutoKeyToValue($key);
    }
    
}
