<?php

declare(strict_types=1);

namespace Mezzio\Tooling\Routes;

use ArrayIterator;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollector;
use Mezzio\Tooling\Routes\Filter\RoutesFilter;
use Mezzio\Tooling\Routes\Sorter\RouteSorterByName;
use Mezzio\Tooling\Routes\Sorter\RouteSorterByPath;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function get_class;
use function implode;
use function in_array;
use function json_encode;
use function strtolower;
use function usort;

class ListRoutesCommand extends Command
{
    /** @var array<int, Route>  */
    private array $routes = [];

    private ContainerInterface $container;

    private ConfigLoaderInterface $configLoader;

    /** @var array<string,string|array> */
    private array $filterOptions = [];

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

    private const MSG_EMPTY_ROUTING_TABLE = "There are no routes in the application's routing table.";

    /** @var null|string Cannot be defined explicitly due to parent class */
    public static $defaultName = 'mezzio:routes:list';

    public function __construct(
        ContainerInterface $container,
        ConfigLoaderInterface $configLoader
    ) {
        $this->container    = $container;
        $this->configLoader = $configLoader;

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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = 0;

        $this->configLoader->load();

        /** @var RouteCollector $routeCollector */
        $routeCollector = $this->container->get(RouteCollector::class);
        $this->routes   = $routeCollector->getRoutes();

        if ([] === $this->routes) {
            $output->writeln(self::MSG_EMPTY_ROUTING_TABLE);
            return $result;
        }

        $format = strtolower((string) $input->getOption('format'));

        $sorter = $this->getSortOrder($input) === 'name'
            ? new RouteSorterByName()
            : new RouteSorterByPath();
        usort($this->routes, $sorter);

        $this->filterOptions = [
            'method'     => strtolower((string) $input->getOption('supports-method')),
            'middleware' => strtolower((string) $input->getOption('has-middleware')),
            'name'       => strtolower((string) $input->getOption('has-name')),
            'path'       => strtolower((string) $input->getOption('has-path')),
        ];

        switch ($format) {
            case 'json':
                $output->writeln(json_encode($this->getRows(true)));
                $output->writeln(
                    "Listing the application's routing table in JSON format."
                );
                break;
            case 'table':
            case '':
                $table = new Table($output);
                $table->setHeaderTitle('Routes')
                    ->setHeaders(['Name', 'Path', 'Methods', 'Middleware'])
                    ->setRows($this->getRows(false));
                $table->render();
                $output->writeln(
                    "Listing the application's routing table in table format."
                );
                break;
            case 'format':
            default:
                $result = -1;
                $output->writeln(
                    "Invalid output format supplied. Valid options are 'table' and 'json'"
                );
        }

        return $result;
    }

    public function getRows(bool $requireNames = false): array
    {
        $rows = [];

        $routesIterator = new RoutesFilter(
            new ArrayIterator($this->routes),
            $this->filterOptions
        );

        foreach ($routesIterator as $route) {
            $routeMethods = implode(',', $route->getAllowedMethods() ?? []);
            if ($requireNames) {
                $rows[] = [
                    'name'       => $route->getName(),
                    'path'       => $route->getPath(),
                    'methods'    => $routeMethods,
                    'middleware' => get_class($route->getMiddleware()),
                ];
            } else {
                $rows[] = [
                    $route->getName(),
                    $route->getPath(),
                    $routeMethods,
                    get_class($route->getMiddleware()),
                ];
            }
        }

        return $rows;
    }

    public function getSortOrder(InputInterface $input): string
    {
        $sortOrder = strtolower((string) $input->getOption('sort'));
        return ! in_array($sortOrder, ['name', 'path'])
            ? 'name'
            : $sortOrder;
    }
}
