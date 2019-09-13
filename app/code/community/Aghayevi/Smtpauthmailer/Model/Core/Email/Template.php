<?php

/**
 * Class Aghayev_Smtpauthmailer_Model_Core_Email_Template
 */
class Aghayevi_Smtpauthmailer_Model_Core_Email_Template extends Mage_Core_Model_Email_Template {

    const MODULE_SETTINGS_ENABLED = 'system/aghayev_smtpauthmailer/active';

    const MODULE_SETTINGS_HOST = 'system/aghayev_smtpauthmailer/smtp_host';
    const MODULE_SETTINGS_PORT = 'system/aghayev_smtpauthmailer/smtp_port';

    const MODULE_SETTINGS_AUTH = 'system/aghayev_smtpauthmailer/smtp_auth';
    const MODULE_SETTINGS_SSL = 'system/aghayev_smtpauthmailer/smtp_ssl';

    const MODULE_SETTINGS_USER = 'system/aghayev_smtpauthmailer/smtp_username';
    const MODULE_SETTINGS_PASS = 'system/aghayev_smtpauthmailer/smtp_password';

    /**
     * Send mail to recipient
     *
     * @param   array|string       $email        E-mail(s)
     * @param   array|string|null  $name         receiver name(s)
     * @param   array              $variables    template variables
     * @return  boolean
     **/
    public function send($email, $name = null, array $variables = array())
    {
        if (!$this->isValidForSend()) {
            Mage::logException(new Exception('This letter cannot be sent.')); // translation is intentionally omitted
            return false;
        }

        $emails = array_values((array)$email);
        $names = is_array($name) ? $name : (array)$name;
        $names = array_values($names);
        foreach ($emails as $key => $email) {
            if (!isset($names[$key])) {
                $names[$key] = substr($email, 0, strpos($email, '@'));
            }
        }

        $variables['email'] = reset($emails);
        $variables['name'] = reset($names);

        $transport = null;
        if (!Mage::getStoreConfig(self::MODULE_SETTINGS_ENABLED)) {

            ini_set('SMTP', Mage::getStoreConfig('system/smtp/host'));
            ini_set('smtp_port', Mage::getStoreConfig('system/smtp/port'));
        }
        else {
            /* Set up mail transport config */
            $config = array(
                'ssl' => Mage::getStoreConfig(self::MODULE_SETTINGS_SSL),
                'port' => Mage::getStoreConfig(self::MODULE_SETTINGS_PORT),
                'auth' => Mage::getStoreConfig(self::MODULE_SETTINGS_AUTH),
                'username' => Mage::getStoreConfig(self::MODULE_SETTINGS_USER),
                'password' => Mage::getStoreConfig(self::MODULE_SETTINGS_PASS)
            );

            /* Set up transport package to host */
            $transport = new Zend_Mail_Transport_Smtp(Mage::getStoreConfig(self::MODULE_SETTINGS_HOST), $config);
        }

        $mail = $this->getMail();

        $setReturnPath = Mage::getStoreConfig(self::XML_PATH_SENDING_SET_RETURN_PATH);
        switch ($setReturnPath) {
            case 1:
                $returnPathEmail = $this->getSenderEmail();
                break;
            case 2:
                $returnPathEmail = Mage::getStoreConfig(self::XML_PATH_SENDING_RETURN_PATH_EMAIL);
                break;
            default:
                $returnPathEmail = null;
                break;
        }

        if ($returnPathEmail !== null) {
            $mailTransport = new Zend_Mail_Transport_Sendmail("-f".$returnPathEmail);
            Zend_Mail::setDefaultTransport($mailTransport);
        }

        foreach ($emails as $key => $email) {
            $mail->addTo($email, '=?utf-8?B?' . base64_encode($names[$key]) . '?=');
        }

        $this->setUseAbsoluteLinks(true);
        $text = $this->getProcessedTemplate($variables, true);

        if($this->isPlain()) {
            $mail->setBodyText($text);
        } else {
            $mail->setBodyHTML($text);
        }

        $mail->setSubject('=?utf-8?B?' . base64_encode($this->getProcessedTemplateSubject($variables)) . '?=');
        $mail->setFrom($this->getSenderEmail(), $this->getSenderName());

        try {
            $mail->send($transport);
            $this->_mail = null;
        }
        catch (Exception $e) {
            $this->_mail = null;
            Mage::logException($e);
            return false;
        }

        return true;
    }
}