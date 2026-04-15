<?php
/**
 * MS Piscine - Traitement du formulaire de contact
 * Envoie un email a mspiscine33@gmail.com
 */

header('Content-Type: application/json; charset=utf-8');

// Seules les requetes POST sont acceptees
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Methode non autorisee.']);
    exit;
}

// Honeypot anti-spam : si le champ cache "website" est rempli, on ignore silencieusement
if (!empty($_POST['website'] ?? '')) {
    echo json_encode(['success' => true, 'message' => 'Merci !']);
    exit;
}

// Recuperation et nettoyage des champs
function clean($value) {
    return trim(strip_tags((string) $value));
}

$nom       = clean($_POST['nom']       ?? '');
$prenom    = clean($_POST['prenom']    ?? '');
$email     = clean($_POST['email']     ?? '');
$telephone = clean($_POST['telephone'] ?? '');
$projet    = clean($_POST['projet']    ?? '');
$ville     = clean($_POST['ville']     ?? '');
$message   = clean($_POST['message']   ?? '');

// Validation
$errors = [];
if ($nom === '')                                             $errors[] = 'Le nom est requis.';
if ($prenom === '')                                          $errors[] = 'Le prenom est requis.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
if ($telephone === '')                                       $errors[] = 'Le telephone est requis.';
if ($message === '' || strlen($message) < 10)                $errors[] = 'Merci de decrire votre projet (10 caracteres minimum).';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// Destinataire
$to = 'mspiscine33@gmail.com';

// Sujet
$projet_label = $projet !== '' ? ' [' . ucfirst($projet) . ']' : '';
$subject = '=?UTF-8?B?' . base64_encode('Nouvelle demande de devis' . $projet_label . ' - ' . $prenom . ' ' . $nom) . '?=';

// Corps du mail (texte brut)
$body  = "Nouvelle demande de devis recue via le site MS Piscine\r\n";
$body .= str_repeat('-', 55) . "\r\n\r\n";
$body .= "Nom         : $nom\r\n";
$body .= "Prenom      : $prenom\r\n";
$body .= "Email       : $email\r\n";
$body .= "Telephone   : $telephone\r\n";
if ($projet !== '') $body .= "Type projet : $projet\r\n";
if ($ville !== '')  $body .= "Ville       : $ville\r\n";
$body .= "\r\nMessage :\r\n";
$body .= str_repeat('-', 55) . "\r\n";
$body .= $message . "\r\n";
$body .= str_repeat('-', 55) . "\r\n\r\n";
$body .= "Date : " . date('d/m/Y H:i') . "\r\n";
$body .= "IP   : " . ($_SERVER['REMOTE_ADDR'] ?? 'inconnue') . "\r\n";

// Expediteur : on utilise l'hote du site pour eviter les filtres spam
$host = $_SERVER['HTTP_HOST'] ?? 'mspiscine.fr';
$host = preg_replace('/^www\./', '', $host);
$fromAddress = 'contact@' . $host;

$headers  = 'From: MS Piscine <' . $fromAddress . '>' . "\r\n";
$headers .= 'Reply-To: ' . $prenom . ' ' . $nom . ' <' . $email . '>' . "\r\n";
$headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";
$headers .= 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
$headers .= 'Content-Transfer-Encoding: 8bit' . "\r\n";

// Envoi
$sent = @mail($to, $subject, $body, $headers, '-f' . $fromAddress);

if ($sent) {
    echo json_encode([
        'success' => true,
        'message' => 'Merci ' . $prenom . ' ! Votre demande a bien ete envoyee. Nous vous recontacterons sous 24h.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "L'envoi a echoue. Merci de nous contacter directement par telephone au 06 37 29 25 65."
    ]);
}
