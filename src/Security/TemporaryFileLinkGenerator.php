<?php

namespace App\Security;

use phpDocumentor\Reflection\Types\This;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TemporaryFileLinkGenerator
{
    const EXPIRATION_TIME = 15;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator
    )
    {
    }

    public function generateSecureLink(string $fileId): string
    {
        $expirationTimestamp = time() + TemporaryFileLinkGenerator::EXPIRATION_TIME;

        // Create data to include in the token (file id + expiration)
        $data = $fileId . $expirationTimestamp;

        $token = hash_hmac('sha256', $data, $_ENV['ENCRYPTION_KEY']);

        $encodedSignature = urlencode(base64_encode(json_encode([
            'file' => $fileId,
            'expires' => $expirationTimestamp,
            'token' => $token,
        ])));

        return $this->urlGenerator->generate('app_download_file', [
            'signature' => $encodedSignature
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function validateLink(string $encodedSignature)
    {
        $decodedData = json_decode(base64_decode(urldecode($encodedSignature), true), true);

        if (!$decodedData || !isset($decodedData['file'], $decodedData['expires'], $decodedData['token'])) {
            return false;
        }

        // Check if the link has expired
        if ($decodedData['expires'] < time()) {
            return false;
        }

        // Verify the token
        $expectedSignature = hash_hmac('sha256', $decodedData['file'] . $decodedData['expires'], $_ENV['ENCRYPTION_KEY']);

        return hash_equals($expectedSignature, $decodedData['token']) ? $decodedData : false;
    }

    public function generateBinaryFileResponse(string $filePath): BinaryFileResponse
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException('Fichier non trouvé.');
        }

        $response = new BinaryFileResponse($filePath);

        // Set cache headers to prevent caching of the response
        $response->setPrivate();
        $response->headers->add([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);

        // Force the file to download with a predefined filename
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filePath)
        );

        return $response;
    }
}