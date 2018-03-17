<?php

function simpleTextField($formid,$name,$value,$label,$placeholder,$readonly,$invalid) {
    $s='';
    $validity=((isset($invalid[$name]))?((count($invalid[$name])>0)?' is-invalid':' is-valid'):'');
    $s.='<div class="form-group row">';
    $s.='<label for="'.$formid.$name.'" class="col-sm-2 col-form-label">'.$label.'</label>';
    $s.='<div class="col-sm-10"><input type="text" class="form-control'.(($readonly)?'-plaintext readonly':'').$validity.'" name="'.$name.'" id="'.$formid.$name.'" placeholder="'.htmlspecialchars($placeholder).'" value="'.htmlspecialchars($value).'" />';
    if (isset($invalid[$name])) {
        if (count($invalid[$name])>0) {
            $s.='<div class="invalid-feedback">'.join(" ",$invalid[$name]).'</div>';
        } else {
            $s.='<div class="valid-feedback">OK</div>';
        }
    }
    $s.='</div></div>';
    return $s;
}

function simplePasswordField($formid,$name,$value,$label,$invalid) {
    $s='';
    $validity=((isset($invalid[$name]))?((count($invalid[$name])>0)?' is-invalid':' is-valid'):'');
    $s.='<div class="form-group row">';
    $s.='<label for="'.$formid.$name.'" class="col-sm-2 col-form-label">'.$label.'</label>';
    $s.='<div class="col-sm-10"><input type="password" class="form-control'.$validity.'" name="'.$name.'" id="'.$formid.$name.'" placeholder="Entrez ici un mot de passe" value="'.htmlspecialchars($value).'" />';
    if (isset($invalid[$name])) {
        if (count($invalid[$name])>0) {
            $s.='<div class="invalid-feedback">'.join(" ",$invalid[$name]).'</div>';
        } else {
            $s.='<div class="valid-feedback">OK</div>';
        }
    }
    $s.='</div></div>';
    return $s;
}

function doublePasswordField($formid,$name,$value,$label,$invalid) {
    $s='';
    $nameconfirmation=$name.'confirmation';
    $validity=((isset($invalid[$name]))?((count($invalid[$name])>0)?' is-invalid':' is-valid'):'');
    $validityconfirmation=((isset($invalid[$nameconfirmation]))?((count($invalid[$nameconfirmation])>0)?' is-invalid':' is-valid'):'');
    $s.='<div class="form-group row">';
    $s.='<label for="'.$formid.$name.'" class="col-sm-2 col-form-label">'.$label.'</label>';
    $s.='<div class="col-sm-10"><input type="password" class="form-control'.$validity.'" name="'.$name.'" id="'.$formid.$name.'" placeholder="Entrez ici un mot de passe" value="'.htmlspecialchars($value).'" />';
    if (isset($invalid[$name])) {
        if (count($invalid[$name])>0) {
            $s.='<div class="invalid-feedback">'.join(" ",$invalid[$name]).'</div>';
        } else {
            $s.='<div class="valid-feedback">OK</div>';
        }
    }
    $s.='</div></div>';
    $s.='<div class="form-group row">';
    $s.='<label for="'.$formid.$nameconfirmation.'" class="col-sm-2 col-form-label">'.$label.' (confirmation)</label>';
    $s.='<div class="col-sm-10"><input type="password" class="form-control'.$validityconfirmation.'" name="'.$name.'confirmation" id="'.$formid.$name.'confirmation" placeholder="Confirmez le mot de passe" value="'.htmlspecialchars($value).'" />';
    if (isset($invalid[$nameconfirmation])) {
        if (count($invalid[$nameconfirmation])>0) {
            $s.='<div class="invalid-feedback">'.join(" ",$invalid[$nameconfirmation]).'</div>';
        } else {
            $s.='<div class="valid-feedback">OK</div>';
        }
    }
    $s.='</div></div>';
    return $s;
}

function simpleHiddenField($formid,$name,$value) {
    $s='';
    $s.='<input type="hidden" name="'.$name.'" id="'.$formid.$name.'" value="'.htmlspecialchars($value).'" />';
    return $s;
}

function simpleSelect($formid,$name,$values,$value,$label,$placeholder,$fn,$invalid) {
    $s='';
    $validity=((isset($invalid[$name]))?((count($invalid[$name])>0)?' is-invalid':' is-valid'):'');
    $s.='<div class="form-group row">';
    $s.='<label for="'.$formid.$name.'" class="col-sm-2 col-form-label">'.$label.'</label>';
    $s.='<div class="col-sm-10"><select name="'.$name.'" id="'.$formid.$name.'" class="custom-select'.$validity.'">';
    if (!in_array($value,$values)) {
        $s.='<option value="" selected>'.$placeholder.'</option>';
    }
    foreach ($values as $k => $v) {
        $text=$v;
        if ($fn !== null) {
            $text=call_user_func($fn,$text);
        }
        $s.='<option '.($value==$v?'selected ':'').'value="'.htmlspecialchars($v).'">'.htmlspecialchars($text).'</option>';
    }
    $s.='</select>';
    if (isset($invalid[$name])) {
        if (count($invalid[$name])>0) {
            $s.='<div class="invalid-feedback">'.join(" ",$invalid[$name]).'</div>';
        } else {
            $s.='<div class="valid-feedback">OK</div>';
        }
    }
    $s.='</div></div>';
    return $s;    
}
