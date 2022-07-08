<?php

namespace Mezzio\Tooling\Routes;

use Mezzio\Router\RouteCollector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

class ListRoutesCommand extends Command
{
    private RouteCollector $routeCollector;

    private const HELP = <<<'EOT'
        Prints the application's routing table.
        
        For each route, it prints its name, path, middleware, and any additional 
        options, in a tabular format to the terminal. The routes are listed in no 
        particular order, by default. 
        EOT;

    private const HELP_OPT_FORMAT = <<<'EOT'
        These set the format of the command's output. The supported values are 
        `table`, which is the default, and `json`.
        EOT;

    private const HELP_OPT_HAS_MIDDLEWARE = <<<'EOT'
        Filters out routes by middleware class. This option accepts a 
        comma-separated list of one or more middleware classes. The class names 
        can be fully-qualified, unqualified class names, or a regular expression, 
        supported by the preg_* functions. For example, 
        "\Mezzio\Middleware\LazyLoadingMiddleware,LazyLoadingMiddleware,\Mezzio*".
        EOT;

    private const HELP_OPT_HAS_NAME = <<<'EOT'
        Filters out routes by name. This option accepts a comma-separated list of 
        one or more names. The names can be fixed-strings or regular expressions 
        supported by the preg_* functions. For example, 
        "user,user.register,*.register,user*".
        EOT;

    private const HELP_OPT_HAS_PATH = <<<'EOT'
        Filter out routes by path. This option accepts a comma-separated list of 
        one or more paths. The paths can be a fixed-string or a regular expression, 
        supported by the preg_* functions. For example, "/,/api/ping,*/ping".
        EOT;

    private const HELP_OPT_SORT = <<<'EOT'
        Sort the command's output. The supported values are "name" and "path".
        EOT;

    private const HELP_OPT_SUPPORTS_METHOD = <<<'EOT'
        Filters out routes by HTTP method.  This option accepts a comma-separated 
        list of one or more HTTP methods.
        EOT;

    /** @var null|string Cannot be defined explicitly due to parent class */
    public static $defaultName = 'mezzio:routes:list';

    public function __construct(RouteCollector $routeCollector)
    {
        $this->routeCollector = $routeCollector;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription("Print the application's routing table.");
        $this->setHelp(self::HELP);

        $this->addOption(
            'format',
            'f',
            InputOption::VALUE_REQUIRED,
            self::HELP_OPT_FORMAT
        );
        $this->addOption(
            'has-middleware',
            'w',
            InputOption::VALUE_REQUIRED,
            self::HELP_OPT_HAS_MIDDLEWARE
        );
        $this->addOption(
            'has-name',
            'n',
            InputOption::VALUE_REQUIRED,
            self::HELP_OPT_HAS_NAME);
        $this->addOption(
            'has-path',
            'p',
            InputOption::VALUE_REQUIRED,
            self::HELP_OPT_HAS_PATH
        );
        $this->addOption(
            'sort',
            's',
            InputOption::VALUE_REQUIRED,
            self::HELP_OPT_SORT
        );
        $this->addOption(
            'supports-method',
            'm',
            InputOption::VALUE_REQUIRED,
            self::HELP_OPT_SUPPORTS_METHOD
        );
    }
}