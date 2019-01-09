<?php

namespace splitbrain\JiraDash\Controllers;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Custom Error display
 */
class ErrorHandler extends BaseController
{
    /**
     * Default Error Handler
     *
     * @param Request $request
     * @param Response $response
     * @param \Throwable $error
     * @return ResponseInterface
     */
    public function __invoke(Request $request, Response $response, \Throwable $error)
    {
//        $sentry = $this->container->sentry;
//        if ($sentry !== null) {
//            $sentry->captureException($error, ['extra' => ['info' => $this->recursiveExtras($error)]]);
//        }

        if ($this->container->settings['displayErrorDetails']) {
            $details = $this->recursiveStacktrace($error) . "\n" . $this->recursiveExtras($error);
        } else {
            $details = null;
        }
        return $this->view->render(
            $response,
            'error.twig',
            [
                'title' => get_class($error),
                'severity' => 'danger',
                'error' => $error->getMessage(),
                'details' => $details,
            ]
        )->withStatus(500);
    }

    /**
     * 404 Error Handler
     *
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function notFound(Request $request, Response $response)
    {
        return $this->view->render(
            $response,
            'error.twig',
            [
                'title' => 'Not Found',
                'severity' => 'warning',
                'error' => 'The resource you\'re looking for does not exist.',
            ]
        )->withStatus(404);
    }

    /**
     * Error handler to convert old school warnings, notices, etc to exceptions
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return bool
     * @throws \ErrorException
     */
    public static function errorConverter($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting, so let it fall
            // through to the standard PHP error handler
            return false;
        }
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Get all the stack traces
     *
     * @param \Throwable $error
     * @return string
     */
    protected function recursiveStacktrace(\Throwable $error)
    {
        $details = get_class($error) . "\n"
            . $error->getFile() . ':' . $error->getLine() . "\n"
            . $error->getTraceAsString();

        $previousThrowable = $error->getPrevious();
        if ($previousThrowable !== null) {
            $details .= "\n\n" . $this->recursiveStacktrace($previousThrowable);
        }

        return $details;
    }

    /**
     * Get all the extra data
     *
     * @param \Throwable $error
     * @return string
     */
    protected function recursiveExtras(\Throwable $error)
    {
        $extras = '';

        if (\is_callable([$error, 'getExtra'])) {
            $extra = $error->getExtra();
            if ($extra) $extras .= "\n$extra";
        }

        $previousThrowable = $error->getPrevious();
        if ($previousThrowable !== null) {
            $extras .= "\n\n" . $this->recursiveExtras($previousThrowable);
        }

        return $extras;
    }
}
