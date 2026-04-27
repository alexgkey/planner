<?php

namespace App\Controller\Api;

use App\Entity\Photo;
use App\Security\Voter\EventVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/photos')]
class PhotoUploadController extends AbstractController
{
    private string $tempPath;

    public function __construct(string $kernelProjectDir)
    {
        $this->tempPath = $kernelProjectDir . '/var/uploads/tmp';
    }

    #[Route('/upload', name: 'api_photo_upload', methods: ['POST'])]
    public function process(Request $request): Response
    {
        if (!file_exists($this->tempPath)) {
            mkdir($this->tempPath, 0777, true);
        }

        $file = $request->files->get('filepond');
        if (!$file) {
            return new Response('No file uploaded', Response::HTTP_BAD_REQUEST);
        }

        $tempId = uniqid('photo_', true);
        $file->move($this->tempPath, $tempId);

        return new Response($tempId, Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }

    #[Route('/revert', name: 'api_photo_revert', methods: ['DELETE'])]
    public function revert(Request $request): Response
    {
        $tempId = $request->getContent();
        $filePath = $this->tempPath . '/' . $tempId;

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'api_photo_delete', methods: ['DELETE'])]
    public function delete(Photo $photo, EntityManagerInterface $entityManager): Response
    {
        $event = $photo->getReport()?->getEvent();
        if (null === $event || !$this->isGranted(EventVoter::ADD_REPORT, $event)) {
            return new Response('Access Denied', Response::HTTP_FORBIDDEN);
        }

        $entityManager->remove($photo);
        $entityManager->flush();

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
