<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes;

use ArrayIterator;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollector;
use Mezzio\Tooling\Routes\Filter\RouteFilterOptions;
use Mezzio\Tooling\Routes\Filter\RoutesFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function implode;
use function in_array;
use function is_string;
use function iterator_to_array;
use function json_encode;
use function strtolower;
use function usort;

use const JSON_THROW_ON_ERROR;

final class ListRoutesCommand extends Command
{
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
        Filters out routes by HTTP method. This option accepts a comma-separated 
        list of one or more HTTP methods.
        EOT;

    private const MSG_EMPTY_ROUTING_TABLE = "There are no routes in the application's routing table.";

    /** @var null|string Cannot be defined explicitly due to parent class */
    public static $defaultName = 'mezzio:routes:list';

    public function __construct(
        private readonly RouteCollector $routeCollector,
        private readonly ConfigLoaderInterface $configLoader
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription("Print the application's routing table.");
        $this->setHelp(self::HELP);

        $this->addOption(
            'format',
            null,
            InputOption::VALUE_REQUIRED,
            self::HELP_OPT_FORMAT,
            'table'
        );

        $this->addOption(
            'sort',
            null,
            InputOption::VALUE_REQUIRED,
            self::HELP_OPT_SORT,
            'name'
        );

        // Routing table filter options
        $this->addOption(
            'has-middleware',
            null,
            InputOption::VALUE_REQUIRED,
            self::HELP_OPT_HAS_MIDDLEWARE,
            false
        );
        $this->addOption(
            'has-name',
            null,
            InputOption::VALUE_REQUIRED,
            self::HELP_OPT_HAS_NAME,
            false
        );
        $this->addOption(
            'has-path',
            null,
            InputOption::VALUE_REQUIRED,
            self::HELP_OPT_HAS_PATH,
            false
        );
        $this->addOption(
            'supports-method',
            null,
            InputOption::VALUE_REQUIRED,
            self::HELP_OPT_SUPPORTS_METHOD,
            false
        );
    }

    /** @psalm-suppress MixedAssignment All inputs are mixed */
    private function parseOptions(InputInterface $input): RouteFilterOptions
    {
        $middleware = $input->getOption('has-middleware');
        $name       = $input->getOption('has-name');
        $path       = $input->getOption('has-path');
        $method     = $input->getOption('supports-method');

        return new RouteFilterOptions(
            is_string($middleware) && $middleware !== '' ? $middleware : null,
            is_string($name) && $name !== '' ? $name : null,
            is_string($path) && $path !== '' ? $path : null,
            is_string($method) && $method !== '' ? [$method] : [],
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->configLoader->load();

        $routes = $this->routeCollector->getRoutes();
        if ($routes === []) {
            $output->writeln(self::MSG_EMPTY_ROUTING_TABLE);

            return self::FAILURE;
        }

        // Filter Routes
        /** @psalm-var list<Route> $routes Forcing this type as null will not be present */
        $routes = iterator_to_array(new RoutesFilter(
            new ArrayIterator($routes),
            $this->parseOptions($input),
        ), false);

        // Sort Routes
        $routes = $this->sortRoutes($input, $routes);

        $format = strtolower((string) $input->getOption('format'));

        switch ($format) {
            case 'json':
                $output->writeln(
                    json_encode($this->getRows($routes), JSON_THROW_ON_ERROR),
                    OutputInterface::OUTPUT_RAW,
                );

                return self::SUCCESS;
            case 'table':
            case '':
                $table = new Table($output);
                $table->setHeaderTitle('Routes')
                    ->setHeaders(['Name', 'Path', 'Methods', 'Middleware'])
                    ->setRows($this->getRows($routes));
                $table->render();

                return self::SUCCESS;

            default:
                $output->writeln(
                    "Invalid output format supplied. Valid options are 'table' and 'json'"
                );

                return self::FAILURE;
        }
    }

    /** @param list<Route> $routes */
    private function getRows(array $routes): array
    {
        $rows = [];
        foreach ($routes as $route) {
            $routeMethods = implode(',', $route->getAllowedMethods() ?? []);
            $rows[]       = [
                'name'       => $route->getName(),
                'path'       => $route->getPath(),
                'methods'    => $routeMethods,
                'middleware' => $route->getMiddleware()::class,
            ];
        }

        return $rows;
    }

    /**
     * @param list<Route> $routes
     * @return list<Route>
     */
    private function sortRoutes(InputInterface $input, array $routes): array
    {
        $sortOrder = strtolower((string) $input->getOption('sort'));
        $sortOrder = ! in_array($sortOrder, ['name', 'path'], true)
            ? 'name'
            : $sortOrder;

        if ($sortOrder === 'name') {
            usort($routes, static fn (Route $a, Route $b) => $a->getName() <=> $b->getName());
        } else {
            usort($routes, static fn (Route $a, Route $b) => $a->getPath() <=> $b->getPath());
        }

        return $routes;
    }
}
