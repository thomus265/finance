<?php

// Prevent multiple inclusions
if (defined('MAIN_PHP_LOADED')) {
    return;
}
define('MAIN_PHP_LOADED', true);

// Enable error reporting
ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL);

// Define paths to important directories
define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('HOME', '.' . DIRECTORY_SEPARATOR);
define('CONTROLLER', HOME . 'controller' . DIRECTORY_SEPARATOR);
define('IMG', HOME . '/public/img/');
define('JS', HOME . '/public/js/');
define('CSS', HOME . '/public/css/');
define('ADMIN', HOME . '/admin/');
define('PARTIALS', HOME . '/partials/');
define('PUBLIC', HOME . '/public/');

// Register directories to be included
$modules = [CONTROLLER, ADMIN, PARTIALS];
set_include_path(get_include_path() . PATH_SEPARATOR . implode(PATH_SEPARATOR, $modules));

// Autoloader function definition (with protection against redeclaration)
if (!function_exists('autoload')) {
    function autoload($class_name) {
        $file = ROOT . 'controller/' . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            // Optional: handle the error or log it
            echo "Error: Class {$class_name} not found.";
        }
    }

    // Register the autoloader function
    spl_autoload_register('autoload');
}


//useful functions
if (!function_exists('readMore')) {
    function readMore($content, $limit) {
        $content = substr($content,0,$limit);
        $content = substr($content,0,strrpos($content,' '));
        return $content."...";
    }
}

if (!function_exists('greetings')) {
    function greetings(){
        //Here we define out main variables
    $welcome_text="Welcome!";
    $numeric_date=date("G");

    //Start conditionals based on military time
    if($numeric_date>=0&&$numeric_date<=11)
    $welcome_text="Good Morning";
    else if($numeric_date>=12&&$numeric_date<=17)
    $welcome_text="Good Afternoon";
    else if($numeric_date>=18&&$numeric_date<=23)
    $welcome_text="Good Evening";

    //Display our greeting
    return $welcome_text;

    }
}

if (!function_exists('img_greet')) {
    function img_greet(){
        //Here we define out main variables

    $numeric_date=date("G");

    //Start conditionals based on military time
    if($numeric_date>=0&&$numeric_date<=11)
    $welcome_text="background: url(./public/assets/img/bg/".rand(1, 35).".jpg) no-repeat;";
    else if($numeric_date>=12&&$numeric_date<=17)
    $welcome_text="background: url(./public/assets/img/bg/".rand(1, 35).".jpg) no-repeat;";
    else if($numeric_date>=18&&$numeric_date<=23)
    $welcome_text="background: url(./public/assets/img/bg/".rand(1, 35).".jpg) no-repeat;";

    //Display our greeting
    return $welcome_text;

    }
}

if (!function_exists('getRand')) {
    function getRand($len) {
        $characters = "452&678a9efghi2&ijklm5678o0123&456789pqrstUVwxyzabcD01JKL";
        $string = "";
        for ($i = 0; $i < $len; $i++)
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        return $string;
    }
}
function perc($used, $budget){
    $perc = ($used/$budget)*100;
    $perc = round($perc);
    return $perc;
}
function dateS($date){
    $convert = date("D, jS F Y", strtotime($date));
    return $convert;
}
function dateStr($date){
    $convert = date("jS F Y, h:i:a", strtotime($date));
    return $convert;
}
function create_square_image($original_file, $destination_file=NULL, $square_size = 100)
{

    if (isset($destination_file) and $destination_file != NULL) {
        if (!is_writable($destination_file)) {
            //echo '<p style="color:#FF0000">Oops, the destination path is not writable. Make that file or its parent folder wirtable.</p>';
        }
    }

    // get width and height of original image
    $imagedata = getimagesize($original_file);
    $original_width = $imagedata[0];
    $original_height = $imagedata[1];

    if ($original_width > $original_height) {
        $new_height = $square_size;
        $new_width = $new_height * ($original_width / $original_height);
    }
    if ($original_height > $original_width) {
        $new_width = $square_size;
        $new_height = $new_width * ($original_height / $original_width);
    }
    if ($original_height == $original_width) {
        $new_width = $square_size;
        $new_height = $square_size;
    }

    $new_width = round($new_width);
    $new_height = round($new_height);

    // load the image
    if (substr_count(strtolower($original_file), ".jpg") or substr_count(strtolower($original_file), ".jpeg")) {
        $original_image = imagecreatefromjpeg($original_file);
    }
    if (substr_count(strtolower($original_file), ".gif")) {
        $original_image = imagecreatefromgif($original_file);
    }
    if (substr_count(strtolower($original_file), ".png")) {
        $original_image = imagecreatefrompng($original_file);
    }

    $smaller_image = imagecreatetruecolor($new_width, $new_height);
    $square_image = imagecreatetruecolor($square_size, $square_size);

    imagecopyresampled($smaller_image, $original_image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);

    if ($new_width > $new_height) {
        $difference = $new_width - $new_height;
        $half_difference = round($difference / 2);
        imagecopyresampled($square_image, $smaller_image, 0 - $half_difference + 1, 0, 0, 0, $square_size + $difference, $square_size, $new_width, $new_height);
    }
    if ($new_height > $new_width) {
        $difference = $new_height - $new_width;
        $half_difference = round($difference / 2);
        imagecopyresampled($square_image, $smaller_image, 0, 0 - $half_difference + 1, 0, 0, $square_size, $square_size + $difference, $new_width, $new_height);
    }
    if ($new_height == $new_width) {
        imagecopyresampled($square_image, $smaller_image, 0, 0, 0, 0, $square_size, $square_size, $new_width, $new_height);
    }


    // if no destination file was given then display a png
    if (!$destination_file) {
        imagepng($square_image, NULL, 9);
    }

    // save the smaller image FILE if destination file given
    if (substr_count(strtolower($destination_file), ".jpg")) {
        imagejpeg($square_image, $destination_file, 100);
    }
    if (substr_count(strtolower($destination_file), ".gif")) {
        imagegif($square_image, $destination_file);
    }
    if (substr_count(strtolower($destination_file), ".png")) {
        imagepng($square_image, $destination_file, 9);
    }

    imagedestroy($original_image);
    imagedestroy($smaller_image);
    imagedestroy($square_image);
}

$db = new Database();
$auth = new Auth();
$transaction = new transaction();
$user = new User();
$attachment = new Attachment();
$workflow = new Workflow();
$notification = new Notification();
$security = new Security();
$Visits = new Visits();
///functions

session_start();

if (!function_exists('addComment')) {
    function addComment($author,$email,$comment,$id, $db){
        $insert = $db->query("INSERT INTO comment(news_id,author,email,description) VALUES('$id','$author','$email','$comment')");
        echo 1;
    }
}

        //$check = $sessionManager->checkSession();

        //$userId = $sessionManager->getUserId();
        



        $fields = [
            'name' => 'string',
            'email' => 'email',
            'age' => 'int',
            'weight' => 'float',
            'github' => 'url',
            'hobbies' => 'string[]'
        ];
