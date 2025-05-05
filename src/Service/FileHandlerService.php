<?php

namespace App\Service;

use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class FileHandlerService
{
    public function __construct(
        private Security $security
    )
    {
    }

    public function upload(UploadedFile $file): string
    {
        try {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $fileName = uniqid('file_', true) . '.' . $file->guessExtension();
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException|Exception $e) {
            throw new FileException('Could not upload the file.');
        }
        return $fileName;
    }

    public function remove(string $fileName): void
    {
        $filesystem = new Filesystem();
        $route = $this->getTargetDirectory() . $fileName;
        if ($filesystem->exists($route)) {
            $filesystem->remove($route);
        }
    }

    public function getTargetDirectory(): string
    {
        $folder = $this->security->getUser()->getId();
        return 'uploads/' . $folder . '/';
    }
}