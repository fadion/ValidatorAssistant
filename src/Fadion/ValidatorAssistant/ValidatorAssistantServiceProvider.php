<?php namespace Fadion\ValidatorAssistant;

use Illuminate\Support\ServiceProvider;

class ValidatorAssistantServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('fadion/validator-assistant');
    }

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app['validator-assistant'] = $this->app->share(function($app)
        {
            return new ValidatorAssistant;
        });

        $this->app->booting(function()
        {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('ValidatorAssistant', 'Fadion\ValidatorAssistant\ValidatorAssistant');
        });
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('validator-assistant');
	}

}