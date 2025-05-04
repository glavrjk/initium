<?php

namespace App\Service;

use JsonException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class FormErrorService
{

    /**
     * @throws JsonException
     */
    public function getRequestData(Request $request): ?array
    {
        return json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function getErrorMessages(FormInterface $form): array
    {

        $errors = [];
        foreach ($form->all() as $child) {
            foreach ($child->getErrors() as $error) {
                $name = $child->getName();
                $errors[$name] = $error->getMessage();
            }
        }

        return $errors;
    }

}