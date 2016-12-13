<?php
error_reporting('E_ALL');

include './secret.php';

function isFilled($str) {
  return $str && strlen($str) > 0;
}

function isValidEmail($str) {
  return isFilled($str) && filter_var($str, FILTER_VALIDATE_EMAIL);
}

function isValidRecaptchaToken($token, $ip, $secret) {
  if (!isFilled($token)) return false;

  $url = 'https://www.google.com/recaptcha/api/siteverify';
  $fields = array(
  	'secret' => urlencode($secret),
  	'response' => urlencode($token),
  	'remoteip' => urlencode($ip),
  );

  foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
  rtrim($fields_string, '&');

  $ch = curl_init();
  curl_setopt($ch,CURLOPT_URL, $url);
  curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $out = curl_exec($ch);
  $result = json_decode($out, true);
  curl_close($ch);

  return $result['success'];
}

$ip = $_SERVER['REMOTE_ADDR'];
$name = $_POST['name'] ?: false;
$company = $_POST['business'] ?: false;
$email = $_POST['email'] ?: false;
$phone = $_POST['phone'] ?: false;
$message = $_POST['message'] ?: false;
$recaptcha = $_POST['g-recaptcha-response'] ?: false;

$errors = [];


if (!isFilled($name)) $errors[] = 'Veuillez saisir votre nom.';
if (!isFilled($message)) $errors[] = 'Veuillez saisir un message.';
if (!isValidEmail($email)) $errors[] = 'Veuillez saisir une adresse email valide.';
if (!isFilled($recaptcha)) $errors[] = 'Veuillez cocher la case pour prouver que vous êtes un humain.';

if (!isValidRecaptchaToken($recaptcha, $ip, $recaptcha_secret)) {
  $errors[] = 'La vérification que vous êtes un humain a échoué. Veuillez réessayer.';
}

if (count($errors)) {
  echo implode('<br>', $errors);
  die();
}

$email = "
Nom: $name
Email: $email
Entreprise: $company
Téléphone: $phone

$message
";

$headers = 'From: webmaster@cycloporteur.com' . "\r\n" .
    'Reply-To: info@cycloporteur.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

mail("info@cycloporteur.com", 'Contact sur cycloporteur.com', $email, $headers);
header('Location: ./thanks.html');
