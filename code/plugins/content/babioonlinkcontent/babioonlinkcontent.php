<?php
/**
 * babioon linkcontent
 * @package    BABIOON_LINKCONTENT
 * @author     Robert Deutz <rdeutz@gmail.com>
 * @copyright  2012 Robert Deutz Business Solution
 * @license    GNU General Public License version 2 or later
 **/

// No direct access
defined('_JEXEC') or die;

/**
 * Babioon Link Content Plugin
 *
 * Usage:
 * {babioonlc id=123[&alt=alternative Text for the Link][$class=a class for the link]}
 *
 * Examples:
 * {babioonlc id=1&alt=Welcome}
 *
 * @package  BABIOON_LINKCONTENT
 * @since    2.0
 */
class PlgContentBabioonlinkcontent extends JPlugin
{

	/**
	 * the onContentPrepare event
	 *
	 * @param   string   $context  pluin context
	 * @param   article  &$row     the date
	 * @param   object   &$params  plugin parameter
	 * @param   integer  $page     page number
	 *
	 * @return  void
	 */
	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		// Tag we are lookin for
		$tag = $this->params->get('babioontag', 'babioonlc');

		// Check if we have a tag within the context
		if (strpos($row->text, '{' . $tag) === false )
		{
			return true;
		}

		// Ok there is work to do

		// Expression to search for
		$regex = '/{(' . $tag . ')\s*(.*?)}/i';

		JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_content/models');
		$matches = array();
		preg_match_all($regex, $row->text, $matches, PREG_SET_ORDER);

		for ($i = 0;$i < count($matches);$i++)
		{
			for ($j = 0;$j < count($matches[$i]);$j++)
			{
				$matches[$i][$j] = html_entity_decode($matches[$i][$j]);
			}
		}

		foreach ($matches as $elm)
		{
			parse_str($elm[2], $args);
			$replacement = "";

			if (array_key_exists('id', $args))
			{
				$id  = (int) $args['id'];
				$alt = '';

				if (array_key_exists('alt', $args))
				{
					$alt = $args['alt'];
				}

				$class = '';

				if (array_key_exists('class', $args))
				{
					$class = $args['class'];
				}

				if (!empty($id))
				{
					$replacement = self::getContentLinkById($id, $alt, $class);
				}
			}
			$row->text   = preg_replace('{' . $elm[0] . '}', $replacement, $row->text, 1);
		}

		return true;
	}

	/**
	 * get a content link
	 *
	 * @param   int     $id     id of the content item
	 * @param   string  $alt    alt text for the link
	 * @param   string  $class  class for the link
	 *
	 * @return  string  searchresults
	 */
	function getContentLinkById($id, $alt = '', $class = '')
	{
		if (empty($id))
		{

			return "";
		}

		try
		{
			$model      = JModelLegacy::getInstance('Article', 'ContentModel', array('ignore_request' => true));

			// This is needed because the getItem try to clone the state
			$registry   = new JRegistry;
			$model->setState('params', $registry);
			$item       = $model->getItem($id);

			if ($item->params->get('access-view') == true)
			{
				$item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
				$link       = JRoute::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid));

				if (trim($link) != "")
				{
					$result  = '<a href="' . $link . '"';
					$result .= trim($class) == '' ? '' : ' class="' . $class . '"';
					$result .= '>';
					$result .= trim($alt) == '' ? htmlspecialchars($item->title) : htmlspecialchars($alt);
					$result .= '</a>';
				}
			}
		}
		catch (JException $e)
		{
			return "";
		}

		return $result;
	}
}
