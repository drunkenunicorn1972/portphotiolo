<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\AlbumTicket;
use App\Form\AlbumShareType;
use App\Repository\AlbumRepository;
use App\Repository\AlbumTicketRepository;
use App\Service\EmailLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AlbumShareController extends AbstractController
{
    #[Route('/album/{uuid}/share', name: 'app_album_share', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function shareAlbum(
        string $uuid,
        Request $request,
        AlbumRepository $albumRepository,
        AlbumTicketRepository $ticketRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        EmailLogger $emailLogger
    ): Response {
        $album = $albumRepository->findOneBy(['uuid' => $uuid]);

        if (!$album) {
            throw $this->createNotFoundException('Album not found');
        }

        $form = $this->createForm(AlbumShareType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $emailsString = $data['emails'];
            $message = $data['message'] ?? '';

            // Split and validate emails
            $emails = array_map('trim', explode(',', $emailsString));
            $emails = array_filter($emails, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));

            if (empty($emails)) {
                $emailLogger->logEmailFailed(
                    implode(', ', $emails),
                    'Album Share Invitation',
                    'No valid email addresses provided',
                    'album_share_validation',
                    [
                        'album_id' => $album->getId(),
                        'album_name' => $album->getName(),
                        'sender' => $this->getUser()?->getEmail()
                    ]
                );

                $this->addFlash('error', 'No valid email addresses provided.');
                return $this->redirectToRoute('app_album_share', ['uuid' => $uuid]);
            }

            $successCount = 0;
            $failedEmails = [];

            foreach ($emails as $emailAddress) {
                $ticket = null;
                try {
                    // Create ticket
                    $ticket = new AlbumTicket();
                    $ticket->setAlbum($album);
                    $ticket->setEmail($emailAddress);
                    $ticket->setMessage($message);

                    $entityManager->persist($ticket);
                    $entityManager->flush();

                    // Generate access link
                    $accessUrl = $this->generateUrl(
                        'app_album_view_with_ticket',
                        ['uuid' => $album->getUuid(), 'ticket' => $ticket->getUuid()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );

                    $subject = 'You\'ve been invited to view an album: ' . $album->getName();

                    // Send email
                    $email = (new Email())
                        ->from($this->getParameter('app.mail_from') ?? 'noreply@example.com')
                        ->to($emailAddress)
                        ->subject($subject)
                        ->html($this->renderView('email/share_album_invite.html.twig', [
                            'album' => $album,
                            'message' => $message,
                            'accessUrl' => $accessUrl,
                            'senderName' => $this->getUser()->getEmail()
                        ]));

                    $mailer->send($email);

                    // Log successful email
                    $emailLogger->logEmailSent(
                        $emailAddress,
                        $subject,
                        'album_share_invitation',
                        [
                            'album_id' => $album->getId(),
                            'album_uuid' => (string) $album->getUuid(),
                            'album_name' => $album->getName(),
                            'ticket_id' => $ticket->getId(),
                            'ticket_uuid' => (string) $ticket->getUuid(),
                            'sender' => $this->getUser()?->getEmail(),
                            'has_personal_message' => !empty($message)
                        ]
                    );

                    $successCount++;
                } catch (\Exception $e) {
                    $failedEmails[] = $emailAddress;

                    // Log failed email with detailed error
                    $emailLogger->logEmailFailed(
                        $emailAddress,
                        'Album Share Invitation: ' . $album->getName(),
                        $e->getMessage(),
                        'album_share_invitation',
                        [
                            'album_id' => $album->getId(),
                            'album_uuid' => (string) $album->getUuid(),
                            'album_name' => $album->getName(),
                            'ticket_id' => $ticket?->getId(),
                            'sender' => $this->getUser()?->getEmail(),
                            'exception_class' => get_class($e),
                            'exception_trace' => $e->getTraceAsString()
                        ]
                    );

                    // Log error but continue with other emails
                    $this->addFlash('warning', 'Failed to send invitation to ' . $emailAddress);
                }
            }

            // Log summary
            $emailLogger->logEmailSent(
                'BATCH',
                'Album Share Batch Summary',
                'album_share_batch_complete',
                [
                    'album_id' => $album->getId(),
                    'album_name' => $album->getName(),
                    'total_recipients' => count($emails),
                    'successful' => $successCount,
                    'failed' => count($failedEmails),
                    'failed_emails' => $failedEmails,
                    'sender' => $this->getUser()?->getEmail()
                ]
            );

            if ($successCount > 0) {
                $this->addFlash('success', sprintf(
                    'Successfully sent %d invitation%s!',
                    $successCount,
                    $successCount > 1 ? 's' : ''
                ));
            }

            if (!empty($failedEmails)) {
                $this->addFlash('error', sprintf(
                    'Failed to send invitations to: %s',
                    implode(', ', $failedEmails)
                ));
            }

            return $this->redirectToRoute('app_album_view', ['uuid' => $uuid]);
        }

        return $this->render('album/share_form.html.twig', [
            'album' => $album,
            'form' => $form->createView()
        ]);
    }
}
