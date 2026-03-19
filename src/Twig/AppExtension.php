<?php

namespace App\Twig;

use App\Security\Permissions\AppPermissions;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        return [
            'Permissions' => new AppPermissions(),
        ];
    }
}