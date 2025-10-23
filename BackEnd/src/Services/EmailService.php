<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Psr\Log\LoggerInterface;

class EmailService {
    private $mailer;
     private $logger;
    public function __construct($config, LoggerInterface $logger) {
        $this->mailer = new PHPMailer(true);
        $this->logger = $logger;
    
        $this->mailer->isSMTP();
        $this->mailer->Host = $config['host'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $config['username'];
        $this->mailer->Password = $config['password'];
        $this->mailer->SMTPSecure = $config['secure'];
        $this->mailer->Port = $config['port'];
    
        $this->mailer->setFrom($config['from'], $config['from_name']);
        $this->mailer->isHTML(true);
    }
    

    public function sendEmail($to, $subject, $body) {
    try {
        $this->mailer->addAddress($to);
        $this->mailer->Subject = $subject;
        $this->mailer->Body = $body;
        $this->mailer->isHTML(true);

        $this->mailer->send();

        // Limpiar direcciones para futuros envíos
        $this->mailer->clearAddresses();
        $this->logger->info("[".__CLASS__."::".__FUNCTION__."] mail enviado a $to por $subject");
        return true;
        
    } catch (\Exception $e) {
        // Loggear con monolog
        $this->logger->error("[".__CLASS__."::".__FUNCTION__."] enviando mail a $to: ".$e->getMessage());
        return false;
    }
}

}
