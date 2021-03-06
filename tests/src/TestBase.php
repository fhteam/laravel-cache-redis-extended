<?php namespace FHTeam\LaravelRedisCache\Tests;

use Exception;
use FHTeam\LaravelRedisCache\DataLayer\Coder\Eloquent\CollectionCoder;
use FHTeam\LaravelRedisCache\DataLayer\Coder\Eloquent\ModelCoder;
use FHTeam\LaravelRedisCache\DataLayer\Coder\PhpSerializeCoder;
use FHTeam\LaravelRedisCache\DataLayer\Serializer\CoderManagerInterface;
use FHTeam\LaravelRedisCache\DataLayer\Serializer\GenericCoderManager;
use FHTeam\LaravelRedisCache\DataLayer\Serializer\GenericSerializer;
use FHTeam\LaravelRedisCache\DataLayer\Serializer\SerializerInterface;
use FHTeam\LaravelRedisCache\ServiceProvider\Laravel4ServiceProvider;
use FHTeam\LaravelRedisCache\ServiceProvider\Laravel5ServiceProvider;
use FHTeam\LaravelRedisCache\TagVersion\Storage\PlainRedisTagVersionStorage;
use FHTeam\LaravelRedisCache\TagVersion\Storage\TagVersionStorageInterface;
use FHTeam\LaravelRedisCache\TagVersion\TagVersionManager;
use FHTeam\LaravelRedisCache\TagVersion\TagVersionManagerInterface;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Redis as RedisFacade;
use Orchestra\Testbench\TestCase;
use RedisAPI;
use stdClass;

/**
 * Class LaravelAmqpTestBase
 *
 * @package Forumhouse\LaravelAmqp\Tests
 */
class TestBase extends TestCase
{
    public function tearDown()
    {
        // Cache::flush();
        RedisAPI::connection('test_connection')->flushdb();
        //TODO: check why provider has not connected our custom Redis store
    }

    /**
     * Get base path.
     *
     * @return string
     * @throws Exception
     */
    protected function getBasePath()
    {
        $basedir = realpath(__DIR__.'/../../tests/laravel');
        if (!$basedir) {
            throw new Exception("Base directory does not exist");
        }

        return $basedir;
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        /** @var Repository $config */
        $config = $app['config'];

        $this->configureDatabase($app);
        $this->bindClasses($app);
        $this->configureCache($config);
    }

    /**
     * Get package providers.  At a minimum this is the package being tested, but also
     * would include packages upon which our package depends, e.g. Cartalyst/Sentry
     * In a normal app environment these would be added to the 'providers' array in
     * the config/app.php file.
     *
     * @param Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        if (version_compare(Application::VERSION, "5.0", '>=')) {
            return [Laravel5ServiceProvider::class,];
        } else {
            return [Laravel4ServiceProvider::class,];
        }
    }

    /**
     * Getting rid of unused service providers
     *
     * @param  \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getApplicationProviders($app)
    {
        return [
            'Illuminate\Foundation\Providers\ArtisanServiceProvider',
            'Illuminate\Cache\CacheServiceProvider',
            'Illuminate\Filesystem\FilesystemServiceProvider',
            'Illuminate\Foundation\Providers\ConsoleSupportServiceProvider',
            'Illuminate\Database\DatabaseServiceProvider',
            'Orchestra\Database\MigrationServiceProvider',
            'Illuminate\Redis\RedisServiceProvider',
            'Illuminate\Database\SeedServiceProvider',
        ];
    }

    protected function getApplicationAliases($app)
    {
        $result = parent::getApplicationAliases($app);
        $result['Seeder'] = Seeder::class;
        $result['RedisAPI'] = RedisFacade::class;

        return $result;
    }

    /**
     * @param Repository $config
     */
    protected function configureCache(Repository $config)
    {

        $config->set(
            'database.redis',
            [
                'cluster' => false,
                'test_connection' => [
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'database' => 0,
                ],
            ]
        );

        if (version_compare(Application::VERSION, "5.0", '>=')) {
            $cacheConfigKey = 'cache.stores.redis';
        } else {
            $cacheConfigKey = 'cache';
        }

        $config->set(
            $cacheConfigKey,
            [
                'driver' => 'fh-redis',
                'connection' => 'test_connection',
                'prefix' => 'prefix',
                'coders' => [
                    Model::class => ModelCoder::class,
                    Collection::class => CollectionCoder::class,
                    stdClass::class => PhpSerializeCoder::class,
                    'arrays' => function ($value) {
                        if (is_array($value)) {
                            return new PhpSerializeCoder();
                        }

                        return null;
                    },
                ],
            ]
        );
    }

    /**
     * @param Application $app
     */
    protected function bindClasses($app)
    {
        $app->bind(TagVersionManagerInterface::class, TagVersionManager::class);

        $app->bind(
            TagVersionStorageInterface::class,
            function () use ($app) {
                return new PlainRedisTagVersionStorage($app['redis'], 'test_connection', 'tag_test');
            }
        );

        $app->bind(SerializerInterface::class, GenericSerializer::class);
        $app->bind(CoderManagerInterface::class, GenericCoderManager::class);
    }

    /**
     * @param Application $app
     */
    protected function configureDatabase($app)
    {
        $app['config']->set('database.default', 'test');

        $app['config']->set(
            'database.connections.test',
            [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]
        );
    }
}
