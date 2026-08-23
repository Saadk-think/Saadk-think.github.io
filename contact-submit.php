<?php
declare(strict_types=1);

const DESTINATION_EMAIL = 'info@thinktechnologies.net';
const MAX_NAME_LENGTH = 100;
const MAX_EMAIL_LENGTH = 190;
const MAX_PHONE_LENGTH = 30;
const MAX_MESSAGE_LENGTH = 2000;

function redirect_to_form(string $query)
{
    header('Location: index.html?' . $query . '#contact', true, 303);
    exit;
}

function clean_text(string $value, int $maxLength): string
{
    $value = trim(str_replace(["\r\n", "\r"], "\n", $value));
    $value = strip_tags($value);
    return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
}

function csv_safe(string $value): string
{
    if ($value !== '' && preg_match('/^[=+\-@]/', $value) === 1) {
        return "'" . $value;
    }
    return $value;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed');
}

// Honeypot: silently accept automated submissions without storing them.
if (trim((string)($_POST['website'] ?? '')) !== '') {
    redirect_to_form('submitted=1');
}

$name = clean_text((string)($_POST['name'] ?? ''), MAX_NAME_LENGTH);
$email = clean_text((string)($_POST['email'] ?? ''), MAX_EMAIL_LENGTH);
$phone = clean_text((string)($_POST['phone'] ?? ''), MAX_PHONE_LENGTH);
$message = clean_text((string)($_POST['message'] ?? ''), MAX_MESSAGE_LENGTH);
$informationalConsent = (($_POST['informational_sms'] ?? '') === 'yes');
$marketingConsent = (($_POST['marketing_sms'] ?? '') === 'yes');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_to_form('form_error=1');
}

if (($informationalConsent || $marketingConsent) && $phone === '') {
    redirect_to_form('form_error=1');
}

$timestamp = gmdate('c');
$recordId = bin2hex(random_bytes(12));
$ipAddress = clean_text((string)($_SERVER['REMOTE_ADDR'] ?? ''), 64);
$userAgent = clean_text((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 500);
$storageDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'storage';
$recordFile = $storageDirectory . DIRECTORY_SEPARATOR . 'sms-consent-records.csv';

if (!is_dir($storageDirectory)) {
    @mkdir($storageDirectory, 0750, true);
}

$isNewFile = !file_exists($recordFile);
$handle = @fopen($recordFile, 'ab');
if ($handle !== false) {
    if (flock($handle, LOCK_EX)) {
        if ($isNewFile) {
            fputcsv($handle, ['record_id', 'timestamp_utc', 'name', 'email', 'phone', 'informational_sms', 'marketing_sms', 'message', 'ip_address', 'user_agent']);
        }
        fputcsv($handle, array_map('csv_safe', [
            $recordId,
            $timestamp,
            $name,
            $email,
            $phone,
            $informationalConsent ? 'yes' : 'no',
            $marketingConsent ? 'yes' : 'no',
            $message,
            $ipAddress,
            $userAgent,
        ]));
        fflush($handle);
        flock($handle, LOCK_UN);
    }
    fclose($handle);
}

$subject = 'Website inquiry and SMS preferences - ' . str_replace("\n", ' ', $name);
$emailBody = implode("\n", [
    'Record ID: ' . $recordId,
    'Submitted (UTC): ' . $timestamp,
    'Name: ' . $name,
    'Email: ' . $email,
    'Phone: ' . ($phone !== '' ? $phone : 'Not provided'),
    'Informational SMS consent: ' . ($informationalConsent ? 'YES' : 'NO'),
    'Marketing SMS consent: ' . ($marketingConsent ? 'YES' : 'NO'),
    '',
    'Message:',
    $message !== '' ? $message : 'No message provided.',
]);
$headers = [
    'From: Think Technologies Website <no-reply@thinktechnologies.net>',
    'Reply-To: ' . $email,
    'Content-Type: text/plain; charset=UTF-8',
];
@mail(DESTINATION_EMAIL, $subject, $emailBody, implode("\r\n", $headers));

redirect_to_form('submitted=1');
