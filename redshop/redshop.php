<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Finder.Content
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_BASE') or die;

use Joomla\Registry\Registry;

require_once JPATH_ADMINISTRATOR . '/components/com_finder/helpers/indexer/adapter.php';

/**
 * Smart Search adapter for com_redshop.
 *
 * @since  2.5
 */
class PlgFinderRedshop extends FinderIndexerAdapter
{
	/**
	 * The plugin identifier.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $context = 'Redshop';

	/**
	 * The extension name.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $extension = 'com_redshop';

	/**
	 * The sublayout to use when rendering the results.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $layout = 'product';

	/**
	 * The type of content that the adapter indexes.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $type_title = 'Product';

	/**
	 * The table name.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $table = '#__redshop_product';

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Method to update the link information for items that have been changed
	 * from outside the edit screen. This is fired when the item is published,
	 * unpublished, archived, or unarchived from the list view.
	 *
	 * @param   string   $context  The context for the category passed to the plugin.
	 * @param   array    $pks      An array of primary key ids of the category that has changed state.
	 * @param   integer  $value    The value of the state that the category has been changed to.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function onFinderChangeState($context, $pks, $value)
	{

		// We only want to handle categories here.
		if ($context == 'com_redshop.product_detail')
		{
			/*
			 * The category published state is tied to the parent category
			 * published state so we need to look up all published states
			 * before we change anything.
			 */
			foreach ($pks as $pk)
			{
				// Update the item.
				$this->change($pk, 'state', $value);

				// Reindex the item.
				$this->reindex($pk);
			}
		}

		// Handle when the plugin is disabled.
		if ($context == 'com_plugins.plugin' && $value === 0)
		{
			$this->pluginDisable($pks);
		}
	}


	/**
	 * Method to index an item. The item must be a FinderIndexerResult object.
	 *
	 * @param   FinderIndexerResult  $item    The item to index as an FinderIndexerResult object.
	 * @param   string               $format  The item format.  Not used.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	protected function index(FinderIndexerResult $item, $format = 'html')
	{
		// Check if the extension is enabled.
		if (JComponentHelper::isEnabled($this->extension) == false)
		{
			return;
		}

		// Add the type taxonomy data.
		$item->addTaxonomy('Type', 'Product');

		$item->url = $this->getURL($item->id, $this->extension, $this->layout);
		$item->route = 'index.php?option=com_redshop&view=product&pid=14&cid=3&Itemid=0';
		$item->path = FinderIndexerHelper::getContentPath($item->route);

		$item->access = 1;

		// Index the item.
		$this->indexer->index($item);
	}

	/**
	 * Method to setup the indexer to be run.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.5
	 */
	protected function setup()
	{
		return true;
	}

	/**
	 * Method to get the SQL query used to retrieve the list of content items.
	 *
	 * @param   mixed  $query  A JDatabaseQuery object or null.
	 *
	 * @return  JDatabaseQuery  A database object.
	 *
	 * @since   2.5
	 */
	protected function getListQuery($query = null)
	{
		$db = JFactory::getDbo();

		// Check if we can use the supplied SQL query.
		$query = $query instanceof JDatabaseQuery ? $query : $db->getQuery(true)
			->select('a.product_id as id, a.product_name as title, a.product_number as alias, a.product_s_desc AS summary, a.product_desc AS body')
			->select('a.metakey, a.metadesc, a.published as state')
			->from('#__redshop_product AS a');

		return $query;
	}

	/**
	 * Method to get a content item to index.
	 *
	 * @param   integer  $id  The id of the content item.
	 *
	 * @return  FinderIndexerResult  A FinderIndexerResult object.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	protected function getItem($id)
	{
		// Get the list query and add the extra WHERE clause.
		$query = $this->getListQuery();
		$query->where('a.product_id = ' . (int) $id);

		// Get the item to index.
		$this->db->setQuery($query);
		$row = $this->db->loadAssoc();

		// Convert the item to a result object.
		$item = JArrayHelper::toObject($row, 'FinderIndexerResult');

		// Set the item type.
		$item->type_id = $this->type_id;

		// Set the item layout.
		$item->layout = $this->layout;

		return $item;
	}

}
