<?php

namespace App\Service;

use JsonException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class FormHandlerService
{

    /**
     * @throws JsonException
     */
    public function processRequest(Request $request, FormInterface $form): void
    {
        if ($request->headers->get('content-type') === 'application/json') {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            if (!$data) {
                throw new JsonException('Invalid JSON');
            }
            $form->submit($data, false);
            return;
        }
        $form->handleRequest($request);
    }

    public function getErrorMessages(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->all() as $child) {
            foreach ($child->getErrors(true) as $error) {
                $name = $child->getName();
                $errors[$name] = $error->getMessage();
            }
        }
        return ['errors' => $errors];
    }

}