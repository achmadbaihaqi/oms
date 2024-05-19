<?php
/**
 * VFM - veno file manager: ajax/shorten.php
 *
 * Generate short sharing link
 *
 * PHP version >= 5.3
 *
 * @category  PHP
 * @package   VenoFileManager
 * @author    Nicola Franchini <support@veno.it>
 * @copyright 2013 Nicola Franchini
 * @license   Exclusively sold on CodeCanyon
 * @link      http://filemanager.veno.it/
 */
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest')
) {
    exit;
}
require_once  dirname(__DIR__).'/class/class.utils.php';
require_once  dirname(__DIR__).'/class/class.downloader.php';
require_once  dirname(__DIR__).'/class/class.setup.php';

use chillerlan\QRCode\{QRCode, QROptions};
use chillerlan\QRCode\Output\QROutputInterface;

require_once dirname(__DIR__).'/assets/qrcode/vendor/autoload.php';

$utils = new Utils;
$downloader = new Downloader();
$setUp = new SetUp();

$attachments = filter_input(INPUT_POST, "atts", FILTER_SANITIZE_SPECIAL_CHARS);
$time = filter_input(INPUT_POST, "time", FILTER_SANITIZE_SPECIAL_CHARS);
$hash = filter_input(INPUT_POST, "hash", FILTER_SANITIZE_SPECIAL_CHARS);
$lifetime = filter_input(INPUT_POST, "lifetime", FILTER_SANITIZE_NUMBER_INT);
$pass = isset($_POST['pass']) ? $_POST['pass'] : false;

$hpass = false;
if ($pass) {
    if (strlen($pass) > 0) {
        $hpass = md5($pass);
    }
}

$saveData = array();

$saveData['pass'] = $hpass;
$saveData['time'] = $time;
$saveData['hash'] = $hash;
$saveData['attachments'] = $attachments;
if ($lifetime) {
    $saveData['lifetime'] = $lifetime;
}
$json_name = md5($time.$attachments.$pass.$lifetime);
/** 
 * Use this second function
 * to shorten the name to 12 chars instead of default 32
 */
// $json_name = substr(md5($time.$attachments.$pass), 0, 12);

// create the temporary directory
if (!is_dir(dirname(dirname(__FILE__)).'/_content/share')) {
    mkdir(dirname(dirname(__FILE__)).'/_content/share', 0755, true);
}
// save dowloadable link if it does not already exists
if (!file_exists(dirname(dirname(__FILE__)).'/_content/share/'.$json_name.'.json') || $pass!==false) {
    $fp = fopen(dirname(dirname(__FILE__)).'/_content/share/'.$json_name.'.json', 'w');
    fwrite($fp, json_encode($saveData));
    fclose($fp);
}
// remove old files
$shortens = glob(dirname(dirname(__FILE__))."/_content/share/*.json");

foreach ($shortens as $shorten) {
    if (is_file($shorten)) {
        $filetime = filemtime($shorten);

        if (!$downloader->checkTime($filetime, $lifetime)) {
            unlink($shorten);
        }
    }
}

$link = $setUp->getConfig('script_url').'/?dl='.$json_name;

$options = new QROptions(
    [
        'version'             => 7,
        'outputType'          => QROutputInterface::MARKUP_SVG,
        'imageBase64'         => true,
        'addQuietzone'        => true,
        'drawLightModules'    => false,
        'markupDark'          => '',
        'markupLight'         => '',
        'connectPaths'        => true
    ]
);

$qrcode_img = (new QRCode($options))->render($link);

$return_data = array(
    'qrcode' => $qrcode_img,
    'link' => $link
);
echo json_encode($return_data);
exit;
