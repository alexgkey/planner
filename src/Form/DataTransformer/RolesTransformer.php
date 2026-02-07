<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class RolesTransformer implements DataTransformerInterface
{
    /**
     * Преобразует массив ролей в одну строку для формы.
     * (например, ['ROLE_ADMIN'] => 'ROLE_ADMIN')
     */
    public function transform($rolesArray): ?string
    {
        if (null === $rolesArray) {
            return null;
        }

        // Находим первую значащую роль (не ROLE_USER)
        foreach ($rolesArray as $role) {
            if ($role !== 'ROLE_USER') {
                return $role;
            }
        }

        return null;
    }

    /**
     * Преобразует одну строку из формы в массив ролей для сущности.
     * (например, 'ROLE_ADMIN' => ['ROLE_ADMIN'])
     */
    public function reverseTransform($roleString): array
    {
        if (!$roleString) {
            return [];
        }

        return [$roleString];
    }
}
