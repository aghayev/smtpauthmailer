<?php

require_once dirname(__DIR__) . '/../../../../../app/Mage.php';
$app = Mage::app();
Mage::register('isSecureArea', true);

$config = array('auth' => 'login',
    'username' => 'your-username',
    'password' => 'your-password');

$transport = new Zend_Mail_Transport_Smtp('hostname', $config);

$mail = new Zend_Mail();
$mail->setBodyText('Test SMTP mailer');
$mail->setFrom('sender@yourserver.com', 'Some Sender');
$mail->addTo('recepient@yourserver.com', 'Some Recipient');
$mail->setSubject('Some subject');
$mail->send($transport);