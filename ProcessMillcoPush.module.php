<?php
namespace ProcessWire;

/**
 * ProcessMillcoPush
 * 
 * Admin page for managing various mu settings 
 */



class ProcessMillcoPush extends Process implements Module
{

	public $public_key;
	public $private_key;

	public static function getModuleInfo()
	{
		return [
			'title' => 'Millco Push Admin',
			'summary' => 'Process to manage Millco Push',
			'version' => 1,
			'icon' => 'arrow-right',
			'page' => [
				'name' => 'mpush',
				'parent' => 'setup',
				'title' => 'Push',
				'permission' => 'millco-push-manage',
			],
			'autoload' => false,
			'singular' => false,
			'permanent' => false,
			'requires' => [
				'PHP>=8.0.0',
				'ProcessWire>=3.0.0',
				'MillcoPush',
			]
		];
	}


	public function init()
	{
		parent::init();

		// get our config field values
		$this->public_key = $this->get('public_key');

	}

	public function __construct()
	{

	}
	
	public function ___execute()
	{

		$admin_page_markup='';

		// get the main module so we can use some handy functions.
		/** @var MillcoPush $mp */
		$mp = $this->modules->get('MillcoPush');

		if(wire('input')->post('mps_user') && wire('input')->post('mps_content')){
			
			$recipient_id=wire('input')->post('mps_user');
			$message=wire('input')->post('mps_content');

			// Sanitize to a single line with no tags.
			$message=wire('sanitizer')->line($message);

			$success=$mp->notify_user($recipient_id, $message);

			if($success){
				$this->message("Notification sent to {$success} subscribers");
			}else{
				$this->error("No notification sent for this user.");
			}

		}

		// render our form. We pass the mp module to it.
		$admin_page_markup.= wire('files')->render(wire('config')->paths->siteModules . 'MillcoPush/markup/send_notification_form.php', ['mp'=>$mp]);

		return $admin_page_markup;
	}




}
