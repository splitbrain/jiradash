<?php

namespace splitbrain\JiraDash;

use CosmoCode\Matrix\Controllers\ErrorHandler;
use ORM\DbConfig;
use Slim\Views\Twig;
use splitbrain\JiraDash\Service\ConfigurationManager;

/**
 * Class Container
 *
 * @property Twig view The Twig view manager
 */
class Container extends \Slim\Container
{
    /** @var Container */
    static protected $instance;

    /**
     * Returns the initialized singleton instance of the container
     *
     * @return Container
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Container(new ConfigurationManager());
        }
        return self::$instance;
    }

    /**
     * Container constructor.
     * @param ConfigurationManager $config
     */
    public function __construct(ConfigurationManager $config)
    {
        parent::__construct($config->getConfiguration());

        // we want exceptions
        #set_error_handler([ErrorHandler::class, 'errorConverter']);

        $this['config'] = $config;

        // create the Twig view
        $this['view'] = function ($c) {
            $view = new Twig(__DIR__ . '/../resources/views', [
                'cache' => false,
                'debug' => $c->settings['debug'],
            ]);

            //set view variables
            $view->offsetSet('config', $c->settings['app']);

            $view->addExtension(new \Twig_Extension_Debug());
            $view->addExtension(new \Slim\Views\TwigExtension(
                $c->router,
                $c->request->getUri()
            ));
            return $view;
        };

        // custom error handling
        /*
        $this['errorHandler'] = function ($c) {
            return new ErrorHandler($c);
        };
        $this['phpErrorHandler'] = function ($c) {
            return new ErrorHandler($c);
        };
        $this['notFoundHandler'] = function ($c) {
            return function ($request, $response) use ($c) {
                return (new ErrorHandler($c))->notFound($request, $response);
            };
        };
        */
    }
}
