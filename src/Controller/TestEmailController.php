<?php

namespace App\Controller;

use App\Service\EmailService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class TestEmailController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/mail/test', name: 'mail_test')]
    public function index(
        MailerInterface $mailer,
        EmailService $emailService
    ): Response
    {
        $content = '<div>Bonjour, <br>Je voudrais savoir comment procéder? <br>
  <br>Voici ma liste de documents:
</div>
<ul>
  <li>Catégorie 1 </li>
  <ul>
      <li>Doc 1</li>
      <li>Doc 2</li>
  </ul>
  <li>Catégorie 2 </li>
  <ul>
      <li>Doc 3</li>
  </ul>
</ul>
<div>
  <br>Merci pour votre réponse rapide! <br>
  <br>Cordialement, <br>
  <br>Jean-Marie alias le conquérant
</div>';

        $content = $emailService->generateAppropriateHtmlContent($content);

        // Créer un e-mail avec les informations requises
        $email = (new Email())
            ->from(new Address('ahah@gmail.com', 'C\'est moi'))
            ->to('recipient@example.com') // Remplacez par l'adresse du destinataire
            ->subject('Test Email')
//            ->text('This is a test email sent from Symfony.')
            ->html($content);



        // Envoyer l'e-mail
        $mailer->send($email);

        // Retourner une réponse pour indiquer que l'e-mail a été envoyé
        return $this->render('base.html.twig');
    }
}