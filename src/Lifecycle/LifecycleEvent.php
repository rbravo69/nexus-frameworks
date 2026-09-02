<?php

declare(strict_types=1);

namespace Nexus\Lifecycle;

enum LifecycleEvent: string
{
    case BeforeBoot = 'before_boot';
    case AfterRegister = 'after_register';
    case AfterBoot = 'after_boot';
    case BeforeShutdown = 'before_shutdown';
    case AfterShutdown = 'after_shutdown';
}
