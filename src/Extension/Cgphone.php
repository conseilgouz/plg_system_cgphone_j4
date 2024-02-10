<?php
/**
* CG Phone  - Joomla 4.x/5.x Plugin
* Version			: 2.2.0
* @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
* @copyright (c) 2024 ConseilGouz. All Rights Reserved.
* @author ConseilGouz 
* {cgphone=<phone#> | img=<an image>}
**/
namespace ConseilGouz\Plugin\System\CGPhone\Extension;
// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Environment\Browser;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Event\Content\ContentPrepareEvent;

final class Cgphone extends CMSPlugin implements SubscriberInterface
{
	protected $loaded;
	protected $deviceType;
    public $myname='Cgphone';
    private $xmlParser;
    protected $autoloadLanguage = true;
   
    public static function getSubscribedEvents(): array
    {
        return [
			'onAfterRespond' => 'onAfter',
            'onContentPrepare'   => 'onContent'
		];
    }

    public function onAfter()
	{
		$this->clearCacheGroups(array('com_content','com_contact'), array(0, 1));
	}
	public function onContent(ContentPrepareEvent $event)
	{
		if (!$this->loaded) { // load CSS only once
			$this->loaded = true;
			$media	= 'media/plg_system_cgphone/';
			/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
			$wa = Factory::getDocument()->getWebAssetManager();
			$wa->registerAndUseStyle('cgphone',$media.'css/cgphone.css');
			if ($this->params->get('css_gen')) $wa->addInlineStyle($this->params->get('css_gen')); 
		}
		$row = $event->getItem();
		$regex = '/{(cgphone=)\s*(.*?)}/i';
		if ($event->getContext() == "com_contact.contact") {
            preg_match_all($regex, (string)$row->telephone, $matches);
            $row->telephone = $this->go_replace((string)$row->telephone,$matches);
            preg_match_all($regex,(string)$row->mobile, $matches);
            $row->mobile = $this->go_replace((string)$row->mobile,$matches);
            preg_match_all($regex, (string)$row->fax, $matches);
            $row->fax = $this->go_replace((string)$row->fax,$matches);
            
        } else {
            preg_match_all($regex, (string)$row->text, $matches);
            preg_match_all($regex, (string)$row->text, $matches);
            $row->text = $this->go_replace((string)$row->text,$matches);
            
        }
	return true;
	}
    private function go_replace(String $text,$matches){
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
					$replace = '<img class="cghidden_img '.$this->deviceType.'" src="'.URI::base().$img.'" />';
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
				catch (\Exception $e)
				{
					// Ignore it
				}
			}
		}
	}
	
}
