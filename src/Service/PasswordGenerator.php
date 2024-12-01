<?php

namespace App\Service;

use App\Form\ChangePasswordFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Validator\Validation;

class PasswordGenerator
{
    public function __construct(private readonly FormFactoryInterface $formFactory)
    {
    }

    public function generatePassword(int $length)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890.,;:!?/§%$£*+-';
        $charactersLength = strlen($characters) - 1;

        // Create the validator
        $validator = Validation::createValidator();
        $constraints = $this->getPasswordConstraints();
        $j = 0;
        do {
            $passwordArray = array();
            for ($i = 0; $i < $length; $i++) {
                $n = random_int(0, $charactersLength);
                $passwordArray[] = $characters[$n];
            }

            $password = implode($passwordArray);
            $violations = $validator->validate($password, $constraints, ['Default', 'edit']);
            $j++;
        } while (count($violations) > 0);

        return [$password,$j];
    }

    public function getPasswordConstraints(): array
    {
        // Create an instance of the form
        $form = $this->formFactory->create(ChangePasswordFormType::class);

        // Retrieve the 'plainPassword' field
        $plainPasswordField = $form->get('plainPassword')->get('first');

        // Access the field's options
        $options = $plainPasswordField->getConfig()->getOptions();

        // Constraints are stored under the 'constraints' key
        return $options['constraints'] ?? [];
    }
}