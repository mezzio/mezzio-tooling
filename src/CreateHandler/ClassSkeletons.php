<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Tooling\CreateHandler;

class ClassSkeletons
{
    /**
     * @var string Template for request handler class.
     */
    public const CLASS_SKELETON = <<< 'EOS'
<?php

declare(strict_types=1);

namespace %namespace%;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class %class% implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        // Create and return a response
    }
}

EOS;

    /**
     * @var string Template for request handler class that will render a template.
     */
    public const CLASS_SKELETON_WITH_TEMPLATE = <<< 'EOS'
<?php

declare(strict_types=1);

namespace %namespace%;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;

class %class% implements RequestHandlerInterface
{
    /**
     * @var TemplateRendererInterface
     */
    private $renderer;

    public function __construct(TemplateRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        // Do some work...
        // Render and return a response:
        return new HtmlResponse($this->renderer->render(
            '%template-namespace%::%template-name%',
            [] // parameters to pass to template
        ));
    }
}

EOS;
}
