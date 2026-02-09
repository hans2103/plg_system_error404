<?php

declare(strict_types=1);

/**
 * @package        ERROR404
 * @copyright      Copyright (c) 2026 HKweb / hkweb.nl
 * @license        GNU General Public License version 3 or later
 */

namespace HKweb\Plugin\System\Error404\Extension;

use Exception;
use Joomla\CMS\Event\ErrorEvent;
use Joomla\CMS\Event\Model\AfterChangeStateEvent;
use Joomla\CMS\Event\Model\BeforeDeleteEvent;
use Joomla\CMS\Event\Model\BeforeSaveEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;

defined('_JEXEC') or die;

/**
 * ERROR404 System Plugin
 *
 * Intercepts 404 errors and renders custom error pages.
 * Also prevents unpublishing, state changes, and deletion of configured 404 error pages.
 *
 * @since 26.06.00
 */
final class Error404 extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Load the language file on instantiation
	 *
	 * @var    bool
	 * @since  26.06.00
	 */
	protected $autoloadLanguage = true;

	/**
	 * Menu item ID to render for 404 error
	 *
	 * @var    int|null
	 * @since  26.06.00
	 */
	private ?int $error404MenuItemId = null;

	/**
	 * Constructor
	 *
	 * @param   DispatcherInterface  $dispatcher  The event dispatcher
	 * @param   array                $config      The plugin configuration
	 * @param   DatabaseInterface    $database    The database connection
	 *
	 * @since   26.06.00
	 */
	public function __construct(
		DispatcherInterface $dispatcher,
		array $config,
		private readonly DatabaseInterface $database
	) {
		parent::__construct($dispatcher, $config);
	}

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   26.06.00
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			// Protection events (from content plugin)
			'onContentBeforeSave'   => 'onContentBeforeSave',
			'onContentBeforeDelete' => 'onContentBeforeDelete',
			'onContentChangeState'  => 'onContentChangeState',

			// Error handling
			'onError' => 'onError',
		];
	}

	/**
	 * Intercept 404 errors and prepare custom error page content
	 *
	 * @param   ErrorEvent  $event  The error event
	 *
	 * @return  void
	 *
	 * @since   26.06.00
	 */
	public function onError(ErrorEvent $event): void
	{
		$error = $event->getError();

		// Only handle 404 errors
		if ($error->getCode() !== 404)
		{
			return;
		}

		$app = $this->getApplication();
		$langTag = $app->getLanguage()->getTag();

		// Get the configured menu item for this language
		$menuItemId = $this->getError404MenuItemId($langTag);

		if (!$menuItemId)
		{
			return;
		}

		// Store the menu item ID so error.php can use it
		$this->error404MenuItemId = $menuItemId;
		$GLOBALS['error404_menu_item_id'] = $menuItemId;
	}

	/**
	 * Prevent unpublishing or changing state of error pages
	 *
	 * @param   BeforeSaveEvent  $event  The event object
	 *
	 * @return  void
	 *
	 * @since   26.06.00
	 * @throws  Exception
	 */
	public function onContentBeforeSave(BeforeSaveEvent $event): void
	{
		if ($event->getArgument('isNew', false))
		{
			return;
		}

		$context = $event->getArgument('context');

		if ($context === 'com_content.article')
		{
			$this->validateArticleState($event->getArgument('subject'));

			return;
		}

		if ($context === 'com_menus.item')
		{
			$this->validateMenuItemState($event->getArgument('subject'));
		}
	}

	/**
	 * Prevent deletion of error pages
	 *
	 * @param   BeforeDeleteEvent  $event  The event object
	 *
	 * @return  void
	 *
	 * @since   26.06.00
	 * @throws  Exception
	 */
	public function onContentBeforeDelete(BeforeDeleteEvent $event): void
	{
		$context = $event->getArgument('context');

		if ($context === 'com_content.article')
		{
			$this->validateArticleDeletion($event->getArgument('subject'));

			return;
		}

		if ($context === 'com_menus.item')
		{
			$this->validateMenuItemDeletion($event->getArgument('subject'));
		}
	}

	/**
	 * Prevent state changes of error pages
	 *
	 * @param   AfterChangeStateEvent  $event  The event object
	 *
	 * @return  void
	 *
	 * @since   26.06.00
	 * @throws  Exception
	 */
	public function onContentChangeState(AfterChangeStateEvent $event): void
	{
		if ($event->getArgument('value', 1) === 1)
		{
			return;
		}

		$context = $event->getArgument('context');
		$pks = $event->getArgument('pks', []);

		if ($context === 'com_content.article')
		{
			$this->revertArticleStates($pks);

			return;
		}

		if ($context === 'com_menus.item')
		{
			$this->revertMenuItemStates($pks);
		}
	}

	/**
	 * Render 404 page when called from error.php
	 *
	 * This public method allows error.php to request custom 404 page rendering.
	 * It prepares the content and returns true if successful.
	 *
	 * @param   int          $menuItemId  Menu item ID to render
	 * @param   object|null  $document    The error document instance
	 *
	 * @return  bool  True if content was prepared successfully
	 *
	 * @since   26.06.00
	 */
	public function render404PageFromErrorDocument(int $menuItemId, ?object $document = null): bool
	{
		return $this->prepare404Content($menuItemId);
	}

	/**
	 * Get the configured 404 menu item ID for the current language
	 *
	 * @param   string  $langTag  Current language tag
	 *
	 * @return  int|null  Menu item ID or null if not found
	 *
	 * @since   26.06.00
	 */
	private function getError404MenuItemId(string $langTag): ?int
	{
		$pages = $this->params->get('404Pages', []);

		// Try to find menu item for current language
		foreach ($pages as $page)
		{
			if (!isset($page->language, $page->menuItem))
			{
				continue;
			}

			if ($page->language === $langTag)
			{
				return (int) $page->menuItem;
			}
		}

		// Fallback to 'all' languages (*)
		foreach ($pages as $page)
		{
			if (!isset($page->language, $page->menuItem))
			{
				continue;
			}

			if ($page->language === '*')
			{
				return (int) $page->menuItem;
			}
		}

		return null;
	}

	/**
	 * Prepare 404 content
	 *
	 * Sets up the menu item, renders the article, and stores it for template rendering.
	 *
	 * @param   int  $menuItemId  Menu item ID to render
	 *
	 * @return  bool  True if content was prepared successfully
	 *
	 * @since   26.06.00
	 */
	private function prepare404Content(int $menuItemId): bool
	{
		try
		{
			$app = $this->getApplication();

			// Get the menu item
			$menu = $app->getMenu();
			$menuItem = $menu->getItem($menuItemId);

			if (!$menuItem || $menuItem->type === 'separator' || $menuItem->type === 'heading')
			{
				return false;
			}

			// Set the active menu item
			$menu->setActive($menuItemId);

			// Parse the menu item link
			$link = $menuItem->link;

			if (!str_contains($link, 'option='))
			{
				return false;
			}

			parse_str(str_replace('index.php?', '', $link), $queryParams);

			// Set input parameters
			foreach ($queryParams as $key => $value)
			{
				$app->input->set($key, $value);
			}
			$app->input->set('Itemid', $menuItemId);

			// Check if this is a com_content article
			$option = $queryParams['option'] ?? '';
			$view = $queryParams['view'] ?? '';
			$articleId = isset($queryParams['id']) ? (int) $queryParams['id'] : 0;

			if ($option !== 'com_content' || $view !== 'article' || !$articleId)
			{
				return false;
			}

			// Render the article
			$componentOutput = $this->renderArticle($articleId);

			if (!$componentOutput)
			{
				return false;
			}

			// Store the component output for template to use
			$GLOBALS['error_page_component_output'] = $componentOutput;

			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Render a com_content article
	 *
	 * @param   int  $articleId  Article ID to render
	 *
	 * @return  string|null  Rendered HTML or null on failure
	 *
	 * @since   26.06.00
	 */
	private function renderArticle(int $articleId): ?string
	{
		$app = $this->getApplication();

		// Define component path constants
		$componentPath = JPATH_BASE . '/components/com_content';

		if (!defined('JPATH_COMPONENT'))
		{
			define('JPATH_COMPONENT', $componentPath);
		}

		if (!defined('JPATH_COMPONENT_SITE'))
		{
			define('JPATH_COMPONENT_SITE', $componentPath);
		}

		if (!defined('JPATH_COMPONENT_ADMINISTRATOR'))
		{
			define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_content');
		}

		try
		{
			// Load the article model
			$mvcFactory = $app->bootComponent('com_content')->getMVCFactory();
			$articleModel = $mvcFactory->createModel('Article', 'Site', ['ignore_request' => true]);

			// Configure model state
			$articleModel->setState('params', $app->getParams());
			$articleModel->setState('filter.published', 1);
			$articleModel->setState('article.id', $articleId);

			// Get the article
			$article = $articleModel->getItem($articleId);

			if (!$article || !$article->id)
			{
				return null;
			}

			// Create and configure the view
			$view = $mvcFactory->createView('Article', 'Site', 'html');
			$view->setModel($articleModel, true);
			$view->document = $app->getDocument();

			// Render the article
			ob_start();
			$view->display();

			return ob_get_clean();
		}
		catch (Exception $e)
		{
			return null;
		}
	}

	/**
	 * Validate that an article is not being unpublished
	 *
	 * @param   object  $item  The article item
	 *
	 * @return  void
	 *
	 * @since   26.06.00
	 * @throws  Exception
	 */
	private function validateArticleState(object $item): void
	{
		if ($item->state === 1)
		{
			return;
		}

		if (!in_array((int) $item->id, $this->getErrorArticles(), true))
		{
			return;
		}

		throw new Exception(Text::_('PLG_ERROR404_ERROR_STATE'));
	}

	/**
	 * Validate that a menu item is not being unpublished
	 *
	 * @param   object  $item  The menu item
	 *
	 * @return  void
	 *
	 * @since   26.06.00
	 * @throws  Exception
	 */
	private function validateMenuItemState(object $item): void
	{
		if ($item->published === 1)
		{
			return;
		}

		if (!in_array((int) $item->id, $this->getErrorMenuItems(), true))
		{
			return;
		}

		throw new Exception(Text::_('PLG_ERROR404_ERROR_STATE'));
	}

	/**
	 * Validate that an article is not being deleted
	 *
	 * @param   object  $item  The article item
	 *
	 * @return  void
	 *
	 * @since   26.06.00
	 * @throws  Exception
	 */
	private function validateArticleDeletion(object $item): void
	{
		if (!in_array((int) $item->id, $this->getErrorArticles(), true))
		{
			return;
		}

		throw new Exception(Text::_('PLG_ERROR404_ERROR_DELETED'));
	}

	/**
	 * Validate that a menu item is not being deleted
	 *
	 * @param   object  $item  The menu item
	 *
	 * @return  void
	 *
	 * @since   26.06.00
	 * @throws  Exception
	 */
	private function validateMenuItemDeletion(object $item): void
	{
		if (!in_array((int) $item->id, $this->getErrorMenuItems(), true))
		{
			return;
		}

		throw new Exception(Text::_('PLG_ERROR404_ERROR_DELETED'));
	}

	/**
	 * Revert article states back to published
	 *
	 * @param   array  $pks  Primary keys of articles to check
	 *
	 * @return  void
	 *
	 * @since   26.06.00
	 * @throws  Exception
	 */
	private function revertArticleStates(array $pks): void
	{
		$errorArticles = $this->getErrorArticles();

		foreach ($pks as $pk)
		{
			$this->revertArticleState($pk, $errorArticles);
		}
	}

	/**
	 * Revert single article state back to published
	 *
	 * @param   int    $pk             Article primary key
	 * @param   array  $errorArticles  Array of error article IDs
	 *
	 * @return  void
	 *
	 * @since   26.06.00
	 * @throws  Exception
	 */
	private function revertArticleState(int $pk, array $errorArticles): void
	{
		if (!in_array($pk, $errorArticles, true))
		{
			return;
		}

		$query = $this->database->getQuery(true)
			->update($this->database->quoteName('#__content'))
			->set($this->database->quoteName('state') . ' = 1')
			->where($this->database->quoteName('id') . ' = :id')
			->bind(':id', $pk, \Joomla\Database\ParameterType::INTEGER);

		$this->database->setQuery($query)->execute();

		throw new Exception(Text::_('PLG_ERROR404_ERROR_STATE'));
	}

	/**
	 * Revert menu item states back to published
	 *
	 * @param   array  $pks  Primary keys of menu items to check
	 *
	 * @return  void
	 *
	 * @since   26.06.00
	 * @throws  Exception
	 */
	private function revertMenuItemStates(array $pks): void
	{
		$errorMenuItems = $this->getErrorMenuItems();

		foreach ($pks as $pk)
		{
			$this->revertMenuItemState($pk, $errorMenuItems);
		}
	}

	/**
	 * Revert single menu item state back to published
	 *
	 * @param   int    $pk              Menu item primary key
	 * @param   array  $errorMenuItems  Array of error menu item IDs
	 *
	 * @return  void
	 *
	 * @since   26.06.00
	 * @throws  Exception
	 */
	private function revertMenuItemState(int $pk, array $errorMenuItems): void
	{
		if (!in_array($pk, $errorMenuItems, true))
		{
			return;
		}

		$query = $this->database->getQuery(true)
			->update($this->database->quoteName('#__menu'))
			->set($this->database->quoteName('published') . ' = 1')
			->where($this->database->quoteName('id') . ' = :id')
			->bind(':id', $pk, \Joomla\Database\ParameterType::INTEGER);

		$this->database->setQuery($query)->execute();

		throw new Exception(Text::_('PLG_ERROR404_ERROR_STATE'));
	}

	/**
	 * Get array of article IDs used as error pages
	 *
	 * @return  array<int>
	 *
	 * @since   26.06.00
	 */
	private function getErrorArticles(): array
	{
		$pages = $this->params->get('404Pages', []);
		$errorArticles = [];

		foreach ($pages as $page)
		{
			$articleId = $this->extractArticleIdFromMenuLink((int) $page->menuItem);

			if ($articleId === null)
			{
				continue;
			}

			$errorArticles[] = $articleId;
		}

		return $errorArticles;
	}

	/**
	 * Extract article ID from menu item link
	 *
	 * @param   int  $menuItemId  Menu item ID
	 *
	 * @return  int|null  Article ID or null if not found
	 *
	 * @since   26.06.00
	 */
	private function extractArticleIdFromMenuLink(int $menuItemId): ?int
	{
		if ($menuItemId <= 0)
		{
			return null;
		}

		$query = $this->database->getQuery(true)
			->select($this->database->quoteName('link'))
			->from($this->database->quoteName('#__menu'))
			->where($this->database->quoteName('id') . ' = :id')
			->bind(':id', $menuItemId, \Joomla\Database\ParameterType::INTEGER);

		$this->database->setQuery($query);
		$url = $this->database->loadResult();

		if (!$url || !str_contains($url, 'view=article'))
		{
			return null;
		}

		// Parse the URL query string to extract the article ID
		$parts = parse_url($url);

		if (!isset($parts['query']))
		{
			return null;
		}

		parse_str($parts['query'], $params);

		return isset($params['id']) ? (int) $params['id'] : null;
	}

	/**
	 * Get array of menu item IDs used as error pages
	 *
	 * @return  array<int>
	 *
	 * @since   26.06.00
	 */
	private function getErrorMenuItems(): array
	{
		$pages = $this->params->get('404Pages', []);
		$menuItems = [];

		foreach ($pages as $page)
		{
			$menuItems[] = (int) $page->menuItem;
		}

		return $menuItems;
	}
}
