<?php

// This file has been auto-generated by the Symfony Dependency Injection Component for internal use.

if (\class_exists(\ContainerRtayjby\AppTestDebugProjectContainer::class, false)) {
    // no-op
} elseif (!include __DIR__.'/ContainerRtayjby/AppTestDebugProjectContainer.php') {
    touch(__DIR__.'/ContainerRtayjby.legacy');

    return;
}

if (!\class_exists(AppTestDebugProjectContainer::class, false)) {
    \class_alias(\ContainerRtayjby\AppTestDebugProjectContainer::class, AppTestDebugProjectContainer::class, false);
}

return new \ContainerRtayjby\AppTestDebugProjectContainer();
