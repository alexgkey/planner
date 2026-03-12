<?php

namespace App\Twig;

use App\Security\Permissions\AppPermissions;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        // Делаем AppPermissions доступным во всех Twig-шаблонах
        // как глобальную переменную Permissions.
        return [
            'Permissions' => new AppPermissions(),
        ];
    }
}