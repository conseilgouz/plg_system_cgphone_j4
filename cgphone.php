<?php
/**
 * @component     Plugin CG Phone
 * Version			: 2.0.0
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @copyright (c) 2021 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
 * {cgphone=<phone#> | img=<an image>}
**/

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Environment\Browser;
use Joomla\CMS\Plugin\CMSPlugin;

class PlgSystemCgPhone extends CMSPlugin
{
	protected $loaded;
	protected $deviceType;

	public function onAfterRespond()
	{
		$this->clearCacheGroups(array('com_content','com_contact'), array(0, 1));
	}
	public function onContentPrepare($context, &$row, $params, $page = 0)
	{
		if (!$this->loaded) { // load CSS only once
			$this->loaded = true;
			$document = Factory::getDocument();
			$document->addStyleSheet(URI::base() . "plugins/system/cgphone/css/cgphone.css");
			$code= $this->params->get('css_gen');
			$document->addStyleDeclaration($code);
		}
		$regex = '/{(cgphone=)\s*(.*?)}/i';
        if ($context == "com_contact.contact") {
            preg_match_all($regex, $row->telephone, $matches);
            $row->telephone = $this->go_replace($row->telephone,$matches);
            preg_match_all($regex, $row->mobile, $matches);
            $row->mobile = $this->go_replace($row->mobile,$matches);
            preg_match_all($regex, $row->fax, $matches);
            $row->fax = $this->go_replace($row->fax,$matches);
            
        } else {
            preg_match_all($regex, $row->text, $matches);
            preg_match_all($regex, $row->text, $matches);
            $row->text = $this->go_replace($row->text,$matches);
            
        }
	return true;
	}
    private function go_replace($text,$matches){
		$count = count($matches[0]);
		for ($i = 0; $i < $count; $i++) {
			$r  = str_replace('{cgphone=', '', $matches[0][$i]);
			$r  = str_replace('}', '', $r);
			$phone= "";
			$img = "";
			$ex = explode('|', $r);
			$phone	= $ex[0];
			if (array_key_exists('1', $ex)) {
				if (strpos($ex[1],'img') !== false)  $img = trim(str_replace('img=','',$ex[1]));
			}
			$this->deviceType = $this->detectDevice(); // save current device
			if ($this->deviceType =='phone' ) {
				$replace = '<a href="tel:'.$phone.'" target="_blank"><span class="cghidden '.$this->deviceType.'" data-cg="'.$phone.'"></span></a>';
			} else {
				if ($img == "") {
					$replace = '<span class="cghidden '.$this->deviceType.'" data-cg="'.$phone.'"></span>';
				} else {
					$replace = '<img class="cghidden_img '.$this->deviceType.'" src="'.JURI::base().$img.'" />';
				}
			}
			$text = str_replace($matches[0][$i], $replace, $text);
		}    
		return $text;
	}
	// 1.0.2 : utilisation classe Browser
	private function detectDevice()
	{
		$detect = new Browser;
		return ($detect->isMobile() ? ($detect->isRobot() ? 'robot' : 'phone') : 'computer');
	}
	/**
	 * Clears cache groups. We use it to clear the plugins cache after we update the last run timestamp.
	 *
	 * @param   array  $clearGroups   The cache groups to clean
	 * @param   array  $cacheClients  The cache clients (site, admin) to clean
	 *
	 * @return  void
	 *
	 * @since   3.9.0
	 */
	private function clearCacheGroups(array $clearGroups, array $cacheClients = array(0, 1))
	{
		$conf = Factory::getConfig();

		foreach ($clearGroups as $group)
		{
			foreach ($cacheClients as $clientId)
			{
				try
				{
					$options = array(
						'defaultgroup' => $group,
						'cachebase'    => $clientId ? JPATH_ADMINISTRATOR . '/cache' :
							$conf->get('cache_path', JPATH_SITE . '/cache')
					);

					$cache = Cache::getInstance('callback', $options);
					$cache->clean();
				}
				catch (Exception $e)
				{
					// Ignore it
				}
			}
		}
	}
	
}
