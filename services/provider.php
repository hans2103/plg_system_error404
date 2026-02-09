<?php

declare(strict_types=1);

/**
 * @package    Error404
 *
 * @author     HKweb <info@hkweb.nl>
 * @copyright  Copyright (C) 2026 HKweb. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://hkweb.nl
 */

\defined('_JEXEC') or die;

use HKweb\Plugin\System\Error404\Extension\Error404;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

/**
 * Error404 service provider.
 *
 * Configures dependency injection for the Error404 system plugin.
 *
 * @since  26.06.00
 */
return new class () implements ServiceProviderInterface {
	/**
	 * Registers the service provider with the DI container.
	 *
	 * @param   Container  $container  The DI container instance
	 *
	 * @return  void
	 *
	 * @since   26.06.00
	 */
	public function register(Container $container): void
	{
		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$dispatcher = $container->get(DispatcherInterface::class);
				$plugin     = new Error404(
					$dispatcher,
					(array) PluginHelper::getPlugin('system', 'error404'),
					$container->get(DatabaseInterface::class)
				);

				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};
