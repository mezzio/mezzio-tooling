<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Factory;

class CreateFactory
{
    public function __invoke(): Create
    {
        return new Create(new FactoryClassGenerator());
    }
}
