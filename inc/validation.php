<?php

class inputType {

  function customFunction($in) {
    if(isset($this->customFunctionFunc)) {
      $func = $this->customFunctionFunc;
      return $func($in);
    } else return true;
  }
  function inputType($regExp,$identifier,$name="",$min=3,$max=100,$error="",$customFunction=NULL) {
    $this->minLength = $min;
    $this->maxLength = $max;
    $this->regExp = $regExp;
    $this->identifier = $identifier;
    if($customFunction != NULL) {
      $this->customFunctionFunc = $customFunction;
    }
    if($error=="") {
      if($name!="") $name .= " ";
      $this->error = $name."field must be between ".$min." and ".$max." characters long and the following regexp must not be true: ".$regExp;
    } else {
      $this->error = $error;
    }
  }
}

global $inputs;
$inputs = array(
  "user" => new inputType("/[^a-zA-Z0-9_]/","user","Username",1,45,"Your username must be alphanumeric with underscores and be 1 - 45 characters long."),
  "cat" => new inputType("/[^a-zA-Z0-9\s]/","cat","Category",3,45,"Your challenge category must be alphanumeric and be 3 - 45 characters long."),
  "flag" => new inputType("/[^a-zA-Z0-9\s\"]/","flag","Flag",6,50,"Invalid flag. Flags are alphanumeric with spaces only."),
  "title" => new inputType("/[^a-zA-Z0-9_]/","title","Challenge Title",3,35,"Your challenge must be alphanumeric with underscores and be 3 - 35 characters long."),
  "org" => new inputType("/[^a-zA-Z0-9_\s\'\"]/","org","Organization",2,40,"Your organization must be alphanumeric with underscores and spaces and be 3 - 40 characters long."),
  "pass" => new inputType("/^$/","pass","Password",6,500,"Your password must be at least 6 characters long."),
  "pass2" => new inputType("/^$/","pass2","Password",6,500,"Confirmation password must *also* be 6 characters long."),
  "email" => new inputType("/^$/","email","Email",5,500,"You must provide a valid email.",function($in) {return filter_var($in, FILTER_VALIDATE_EMAIL);}),
  "track" => new inputType("/[^a-z0-9]/","track","Tracking Cookie",64,64," "),
  "hint" => new inputType("/^$/","hint","Challenge Hint",6,1125,"Your hint is too long. Must be less than 1125 characters.")
  );

class checkInput {
  private $status = true;
  private $errors = "";
  //receives arrays of format {identifier, name,type}
  //example would be {"user","new-user","POST"}
  function checkInput() {
    if(func_num_args()<1) return false;
    $arg_list = func_get_args();
    foreach ($arg_list as $arg) {
    
      if(!isset($arg['identifier']))
        $arg['identifier'] = $arg[0];
      if(!isset($arg['name']))
        $arg['name'] = $arg[1];
      if(!isset($arg['type']))
        $arg['type'] = $arg[2];
        
        
      if($arg['type']=="POST") {
        $req = $_POST;
      } else if($arg['type']=="GET") {
        $req = $_GET;
      } else if($arg['type']=="COOKIE") {
        $req = $_COOKIE;
      } else {
        $this->status = false;
        return false;
      }
      if(!isset($req[$arg['name']])) {
        $this->status = false;
        $this->errors = "";
        break;
      } else {
        global $inputs;
        $rules = $inputs;
        $rules = $rules[$arg["identifier"]];
        $input = $req[$arg['name']];
        if(strlen($input)<$rules->minLength || strlen($input)>$rules->maxLength || preg_match($rules->regExp,$input) || !$rules->customFunction($input)) {
          $this->errors .= $rules->error."\n";
          $this->status = false;
        }
      }
    }
  }
  function getStatus() {
    return $this->status;
  }
  function getErrors() {
    return $this->errors;
  }
  function paramsNotSet() {
    return ($this->status==false && $this->errors == "");
  }
}
/**
if(($testCases = new checkInput(array("password","pass","GET"),array("email","email","GET"),array("user","user","GET")))) {
  echo $testCases->getStatus();
  echo $testCases->getErrors();
}
**/
?>