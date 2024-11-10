<?php

namespace App\Controller;

use App\Form\Type\DownloadType;
use App\Security\TemporaryFileLinkGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DownloadController extends AbstractController
{
    #[Route('/download', name: 'app_download')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(DownloadType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $codeValue = $form->get('code')->getData();

            if (in_array($codeValue, ['123', '123456', '456789']))
            {
                return $this->redirectToRoute('app_download_files', [
                    'codeValue' => $codeValue
                ]);
            }

            return $this->redirectToRoute('app_download');
        }

        return $this->render('download/index.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/download/files', name: 'app_download_files')]
    public function showFiles(
        Request $request,
        TemporaryFileLinkGenerator $fileLinkGenerator
    ): Response
    {
        $codeValue = $request->get('codeValue');

        if ($codeValue == '123456') {
            return $this->render('download/files.html.twig', [
                'fileLink' => $fileLinkGenerator->generateSecureLink(1),
            ]);
        } elseif ($codeValue == '456789')
        {
            return $this->render('download/files.html.twig', [
                'fileLink' => $fileLinkGenerator->generateSecureLink(2),
            ]);
        } elseif ($codeValue == '123')
        {
            return $this->render('download/files.html.twig', [
                'fileLink' => $fileLinkGenerator->generateSecureLink(3),
            ]);
        }

        return $this->redirectToRoute('app_download');
    }

    #[Route('/download/file/{signature}', name: 'app_download_file')]
    public function downloadFile(
        string $signature,
        TemporaryFileLinkGenerator $fileLinkGenerator
    ): Response
    {
        $decodedData = $fileLinkGenerator->validateLink($signature);

        if (!$decodedData) {
            return new Response('Le lien est invalide ou expiré.', 403);
        } elseif ($decodedData['file'] == 1) {
            return $fileLinkGenerator->generateBinaryFileResponse($this->getParameter('app.uploads_dir').'/cinebillets0522.pdf');
        } elseif ($decodedData['file'] == 2) {
            return $fileLinkGenerator->generateBinaryFileResponse($this->getParameter('app.uploads_dir').'/DALL_Ec2.jpg');
        } else {
            return new Response('Aucun fichier trouvé.', 403);
        }
    }
}
