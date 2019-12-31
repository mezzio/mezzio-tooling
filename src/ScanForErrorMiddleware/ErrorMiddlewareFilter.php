<?php

/**
 * @see       https://github.com/mezzio/mezzio-tooling for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-tooling/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-tooling/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Tooling\ScanForErrorMiddleware;

use FilterIterator;
use Laminas\Code\Scanner\ClassScanner;
use Laminas\Code\Scanner\FileScanner;
use Laminas\Stratigility\ErrorMiddlewareInterface;
use SplFileInfo;

class ErrorMiddlewareFilter extends FilterIterator
{
    /**
     * Filter out non-PHP files from an iterator.
     *
     * @return bool
     */
    public function accept()
    {
        $file = $this->getInnerIterator()->current();

        if (! $file instanceof SplFileInfo
            || $file->isDir()
            || $file->getExtension() !== 'php'
            || ! $file->isReadable()
        ) {
            return false;
        };

        $scanner = new FileScanner((string) $file);
        $classes = $scanner->getClasses();

        if (empty($classes)) {
            return false;
        }

        return array_reduce(
            $classes,
            $this->scanClass($file),
            false
        );
    }

    /**
     * Generate a filter for scanning a file to determine if it contains
     * classes representing error middleware.
     *
     * @param SplFileInfo $file
     * @return callable
     */
    private function scanClass(SplFileInfo $file)
    {
        $file->implementsInterface      = false;
        $file->invokableErrorMiddleware = false;
        $file->callsNextWithError       = false;

        /**
         * @param bool $found
         * @param \Laminas\Code\Scanner\ClassScanner $class
         * @return bool
         */
        return function ($found, $class) use ($file) {
            if ($found) {
                return $found;
            }

            if (in_array(ErrorMiddlewareInterface::class, $class->getInterfaces(), true)) {
                $file->implementsInterface = true;
                return true;
            }

            if ($this->isInvokableErrorMiddleware($class)) {
                $file->invokableErrorMiddleware = true;
                return true;
            }

            if ($this->callsNextWithError($class)) {
                $file->callsNextWithError = true;
                return true;
            }

            return false;
        };
    }

    /**
     * @param ClassScanner $class
     * @return bool
     */
    private function isInvokableErrorMiddleware(ClassScanner $class)
    {
        if (! $class->hasMethod('__invoke')) {
            return false;
        }

        $method = $class->getMethod('__invoke');
        return 4 === $method->getNumberOfParameters();
    }

    /**
     * @param ClassScanner $class
     * @return bool
     */
    private function callsNextWithError(ClassScanner $class)
    {
        return array_reduce(
            $class->getMethods(),
            /**
             * @param bool $found
             * @param \Laminas\Code\Scanner\MethodScanner $method
             * @return bool
             */
            function ($found, $method) {
                if ($found) {
                    return $found;
                }

                if (! in_array('next', $method->getParameters())) {
                    return false;
                }

                return $this->methodCallsNextWithError($method->getBody());
            },
            false
        );
    }

    /**
     * Does the code in the provided method call $next() with an error?
     *
     * @param string $code
     * @return bool
     */
    private function methodCallsNextWithError($code)
    {
        $nextScanner = new NextInvocationScanner($code);
        return $nextScanner->scan();
    }
}
