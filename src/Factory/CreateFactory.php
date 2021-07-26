<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Factory;

final class CreateFactory
{
    public function __invoke(): Create
    {
        return new Create(new FactoryClassGenerator());
    }
}
