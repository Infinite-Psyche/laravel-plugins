<?php
namespace Franktrue\LaravelPlugins;

use Illuminate\Contracts\Foundation\Application;

/**
 * 插件抽象类
 *
 * Class Plugin
 * @package Franktrue\LaravelPlugins
 */
abstract class Plugin
{
    protected $app;

    /**
     * 插件名称 The Plugin Name.
     *
     * @var string
     */
    public $name;

    /**
     * 插件描述 A description of the plugin.
     * 
     * @var string
     */
    public $description;

    /**
     * 插件版本 The version of the plugin.
     * 
     * @var string
     */
    public $version;

    /**
     * @var $this
     */
    private $reflector = null;

    /**
     * Plugin constructor.
     *
     * @param $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->checkPluginName();
    }

    abstract public function boot();

    /**
     * 检查插件名称是否为空 Check for empty plugin name.
     *
     * @throws \InvalidArgumentException
     */
    private function checkPluginName()
    {
        if (!$this->name) {
            throw new \InvalidArgumentException('Missing Plugin name.');
        }
    }

    /**
     * 以插件类名称为基础返回以驼峰大小写格式的视图命名空间，并在末尾删除插件。
     *
     * Returns the view namespace in a camel case format based off
     * the plugins class name, with plugin stripped off the end.
     * 
     * Eg: ArticlesPlugin will be accessible through 'plugin:articles::<view name>'
     *
     * @return string
     */
    protected function getViewNamespace()
    {
        return 'plugin:' . camel_case(
            mb_substr(
                get_called_class(),
                strrpos(get_called_class(), '\\') + 1,
                -6
            )
        );
    }

    /**
     * 启用此插件配置信息
     *
     * @param  string  $path
     * @param  string  $key
     * @return void
     */
    protected function enableConfig($path = 'config.php', $key='')
    {
        $path = $this->getPluginPath(). DIRECTORY_SEPARATOR . $path;
        if(empty($key)) $key = 'plugin_'.$this->name;
        $config = $this->app['config']->get($key, []);
        $this->app['config']->set($key, array_merge(require $path, $config));
    }

    /**
     * 启用此插件的路由。 Enable routes for this plugin.
     *
     * @param string $path
     */
    protected function enableRoutes($path = 'routes.php')
    {
        $this->app->router->group(['namespace' => $this->getPluginControllerNamespace()], function ($app) use ($path) {
            require $this->getPluginPath() . DIRECTORY_SEPARATOR . $path;
        });
    }

    /**
     * 为此插件添加视图命名空间。Add a view namespace for this plugin.
     * Eg: view("plugin:articles::{view_name}")
     *
     * @param string $path
     */
    protected function enableViews($path = 'views')
    {
        $this->app['view']->addNamespace(
            $this->getViewNamespace(),
            $this->getPluginPath(). DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $path
        );
    }

    /**
     * 注册此插件的数据库迁移路径。Register a database migration path for this plugin.
     *
     * @param  array|string  $paths
     * @return void
     */
    protected function enableMigrations($paths = 'migrations')
    {
        $this->app->afterResolving('migrator', function ($migrator) use ($paths) {
            foreach ((array) $paths as $path) {
                $migrator->path($this->getPluginPath(). DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . $path);
            }
        });
    }

    /**
     * 获取插件路径 Get the plugin path
     *
     * @return string
     */
    public function getPluginPath()
    {
        $reflector = $this->getReflector();
        $fileName  = $reflector->getFileName();

        return dirname($fileName);
    }
    
    /**
     * 获取插件控制器命名空间
     *
     * @return string
     */
    protected function getPluginControllerNamespace()
    {
        $reflector = $this->getReflector();
        $baseDir   = str_replace($reflector->getShortName(), '', $reflector->getName());

        return $baseDir . 'Http\\Controllers';
    }

    /**
     * @return \ReflectionClass
     */
    private function getReflector()
    {
        if (is_null($this->reflector)) {
            $this->reflector = new \ReflectionClass($this);
        }

        return $this->reflector;
    }

    /**
     * 返回一个插件视图 Returns a plugin view
     *
     * @param $view
     * @return \Illuminate\View\View
     */
    protected function view($view)
    {
        return view($this->getViewNamespace() . '::' . $view);
    }
}
