<?php

namespace Mezzio\Tooling\Factory;

class CreateFactory
{
    public function __invoke(): Create
    {
        return new Create(new FactoryClassGenerator());
    }
}
