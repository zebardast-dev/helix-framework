<?php

namespace Helix\View;

class Composer
{
    public static function composer($views, $class): void
    {
        ComposerManager::composer($views, $class);
    }
}
