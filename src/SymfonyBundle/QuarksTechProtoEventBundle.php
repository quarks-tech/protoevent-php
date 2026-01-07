<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\SymfonyBundle;

use QuarksTech\ProtoEvent\SymfonyBundle\EventHandler\EventHandlerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class QuarksTechProtoEventBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new EventHandlerPass());
    }
}
