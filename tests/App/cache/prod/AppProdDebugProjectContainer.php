<?php

// This file has been auto-generated by the Symfony Dependency Injection Component for internal use.

if (\class_exists(\ContainerLigfzvn\AppProdDebugProjectContainer::class, false)) {
    // no-op
} elseif (!include __DIR__.'/ContainerLigfzvn/AppProdDebugProjectContainer.php') {
    touch(__DIR__.'/ContainerLigfzvn.legacy');

    return;
}

if (!\class_exists(AppProdDebugProjectContainer::class, false)) {
    \class_alias(\ContainerLigfzvn\AppProdDebugProjectContainer::class, AppProdDebugProjectContainer::class, false);
}

return new \ContainerLigfzvn\AppProdDebugProjectContainer();
