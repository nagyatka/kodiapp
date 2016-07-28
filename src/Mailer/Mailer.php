<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 27.
 * Time: 8:35
 */

namespace KodiApp\Mailer;


use KodiApp\Application;

class Mailer
{
    /**
     * @var string
     */
    private $debugMail;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * Mailer constructor.
     * @param $configuration
     */
    public function __construct($configuration)
    {
        $transport = new \Swift_SmtpTransport($configuration["host"],$configuration["port"]);

        $transport->setUsername($configuration["username"]);
        $transport->setPassword($configuration["password"]);
        $transport->setAuthMode($configuration["auth_mode"]);
        $transport->setEncryption($configuration["encryption"]);

        $this->mailer = new \Swift_Mailer($transport);

        $this->debugMail = isset($configuration["debug_email"]) ? $configuration["debug_email"] : null;
    }


    public function getMessage() {
        return  \Swift_Message::newInstance();
    }

    public function send(\Swift_Message $message) {

        $this->mailer->send($message);
    }

    /**
     * Required parameters:
     *  from,to,subject,body_html,body_plain
     *
     * Optional parameters:
     * cc
     *
     * @param array $emailParameters
     */
    public function sendEmail($emailParameters) {
        $email = \Swift_Message::newInstance();
        $email->setSubject($emailParameters["subject"])
            ->setFrom($emailParameters["from"])
            ->setTo(
                $this->debugMail == null && Application::isDevelopmentEnv() ?
                    $emailParameters["to"]:
                    $this->debugMail)
            ->setBody($emailParameters["body_html"],'text/html');
        if(isset($emailParameters["cc"])) {
            $email->setCc($emailParameters["cc"]);
        }
        $email->addPart($emailParameters["body_plain"],'text/plain');
        $this->mailer->send($email);
    }


}