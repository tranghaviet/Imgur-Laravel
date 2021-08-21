<?php namespace Redeman\Imgur;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Imgur\Client;

class ImgurServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            $this->defaultConfig() => config_path('imgur.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom($this->defaultConfig(), 'imgur');

        $this->app->singleton('Imgur\Client', function() {
            $config = $this->config();
            // Setup the client
            $client = new Client;
            $client->setOption('client_id', $config['client_id']);
            $client->setOption('client_secret', $config['client_secret']);

            return $client;
        });

        // Make the token storage configurable
        $this->app->bind(\Redeman\Imgur\TokenStorage\Storage::class, $this->config()['token_storage']);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['imgur'];
    }

    /**
     * Get the Imgur configuration from the config repository
     *
     * @return array
     */
    public function config()
    {
        return $this->app['config']->get('imgur');
    }

    /**
     * Returns the default configuration path
     *
     * @return string
     */
    public function defaultConfig()
    {
        return __DIR__ . '/config/imgur.php';
    }
}
