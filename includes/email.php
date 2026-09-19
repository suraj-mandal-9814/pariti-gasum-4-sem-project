<?php

/**
 * Send an email-verification code when PHP mail is configured.
 * Returning false lets the registration page display the local fallback code.
 */
function sendVerificationCodeEmail(string $email, string $code): bool
{
    $subject = 'Your Pirati Gasum verification code';
    $message = "Your verification code is: {$code}\r\n\r\nThis code expires in 15 minutes.";
    $headers = "Content-Type: text/plain; charset=UTF-8\r\n";

    return @mail($email, $subject, $message, $headers);
}
