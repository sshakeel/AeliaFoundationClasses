<?php if (!defined('APPLICATION')) exit();

/* Copyright 2013 Diego Zanella (support@pathtoenlightenment.net)
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License, version 3, as
   published by the Free Software Foundation.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.
   GPL3: http://www.gnu.org/licenses/gpl-3.0.txt
*/

require('lib/aeliafoundation.defines.php');
require('lib/aeliafoundation.validationfunctions.php');
// Load Plugin's Autoloader
require_once(AELIAFOUNDATION_PLUGIN_VENDOR_PATH . '/autoload.php');
require('lib/aeliafoundation.overrides.php');

// Define the plugin:
$PluginInfo['AeliaFoundationClasses'] = array(
	'Name' => 'Aelia Foundation Classes',
	'Description' => 'Provides a set of functionalities that can be used by other plugins.',
	'Version' => '13.12.09.001',
	'RequiredApplications' => array('Vanilla' => '2.0.10'),
	'RequiredTheme' => FALSE,
	'RequiredPlugins' => array('Logger' => '13.02.01',
														),
	'MobileFriendly' => TRUE,
	'HasLocale' => FALSE,
	'SettingsUrl' => '/plugin/aeliafoundation',
	'SettingsPermission' => 'Garden.Settings.Manage',
	'Author' => 'D.Zanella',
	'AuthorEmail' => 'diego@pathtoenlightenment.net',
	'AuthorUrl' => 'http://www.pathtoenlightenment.net'
);

class AeliaFoundationClasses extends Gdn_Plugin {
	// @var string The plugin version.
	const VERSION = '13.11.19.001';

	
	const MESSAGE_GROUP_ADMIN = 'admin';
	const MESSAGE_GROUP_USER = 'user';
	// @var array An array containing messages to display to the user.
	protected $Messages = array(
		self::MESSAGE_GROUP_ADMIN => array(),
		self::MESSAGE_GROUP_USER => array(),
	);

	/**
	 * Returns an instance of a Class and stores it as a property of this class.
	 * The function follows the principle of lazy initialization, instantiating
	 * the class the first time it's requested.
	 *
	 * @param string ClassName The Class to instantiate.
	 * @param array Args An array of Arguments to pass to the Class' constructor.
	 * @return object An instance of the specified class.
	 * @throws An Exception if the specified class does not exist.
	 */
	private function GetInstance($ClassName) {
		$FieldName = '_' . $ClassName;
		$Args = func_get_args();
		// Discard the first argument, as it is the Class Name, which doesn't have
		// to be passed to the instance of the Class
		array_shift($Args);

		if(empty($this->$FieldName)) {
			$Reflect  = new ReflectionClass($ClassName);

			$this->$FieldName = $Reflect->newInstanceArgs($Args);
		}

		return $this->$FieldName;
	}

	/**
	 * Class constructor
	 * @return FoundationPlugin An instance of the class.
	 */
	public function __construct() {
		//error_reporting(E_ALL & ~E_STRICT);
	}

	/**
	 * Returns the instance of the plugin.
	 *
	 * @param AeliaFoundationClasses
	 */
	public static function Instance() {
		return Gdn::PluginManager()->GetPluginInstance(get_class($this));
	}

	/**
	 * Adds an array of messages to the list of the messages to display.
	 *
	 * @param array Messages The array of messages to add.
	 * @param int TargetList The target list to which the messages will be appended.
	 */
	
	public function AddMessages(array $Messages, $TargetList = self::MESSAGE_GROUP_ADMIN) {
		$this->Messages[$TargetList] = array_merge($this->Messages[$TargetList], $Messages);
	}

	/**
	 * Clears the list of messages to display.
	 */
	
	public function ClearMessages() {
		$this->Messages = array(
			self::MESSAGE_GROUP_ADMIN => array(),
			self::MESSAGE_GROUP_USER => array(),
		);
	}

	/**
	 * Returns the Override Information array associated to a Override Class.
	 *
	 * @param string Class The name of the class for which to retrieve the
	 * override information.
	 * @return array|null An associative array of Override Information, or null, if
	 * the Override Class could not be found.
	 */
	public static function GetOverrideInfo($Class) {
		if(!defined($Class . '::OVERRIDE_VERSION')) {
			return null;
		}
		$Result = array('Class' => $Class,
										'Version' => $Class::OVERRIDE_VERSION,
										'File' => __FILE__);
		return $Result;
	}

	/**
	 * Base_Render_Before Event Hook
	 *
	 * @param $Sender Sending controller instance.
	 */
	public function Base_Render_Before($Sender) {
		$Sender->AddCssFile('aeliafoundation.css', 'plugins/AeliaFoundationClasses/design/css');

		
		$Sender->AddCssFile('tipTip.css', 'plugins/AeliaFoundationClasses/js/jquery-tiptip');
		$Sender->AddJsFile('jquery.tipTip.js', 'plugins/AeliaFoundationClasses/js/jquery-tiptip');

		//$Sender->AddJsFile($this->GetResource('js/aeliafoundation.js', FALSE, FALSE));
	}

	/**
	 * Create a method called "Foundation" on the PluginController
	 * @param Gdn_Controller Sender Sending controller instance.
	 */
	public function PluginController_AeliaFoundation_Create($Sender) {
		$Sender->Title($this->GetPluginKey('Name'));
		$Sender->AddSideMenu('plugin/aeliafoundation');

		// If your sub-pages use forms, this is a good place to get it ready
		$Sender->Form = new AeliaForm();

		// Dispatch request to appropriate Controller
		$this->Dispatch($Sender, $Sender->RequestArgs);
	}

	/**
	 * Renders the plugin's default page.
	 *
	 * @param Gdn_Controller Sender Sending controller instance.
	 */
	public function Controller_Index($Sender) {
		$this->Controller_Settings($Sender);
	}

	/**
	 * Placeholder method.
	 * Renders a page showing the status of the override of ActivityModel. It can
	 * be used as an example so see how other plugins could display the status of
	 * the classes for which they require an override.
	 *
	 * @param Gdn_Controller Sender Sending controller instance.
	 */
	public function Controller_OverridesList($Sender) {
		$Sender->SetData('CurrentPath', AELIAFOUNDATION_PLUGIN_OVERRIDES_LIST_URL);
		// Prevent non-admins from accessing this page
		$Sender->Permission('Vanilla.Settings.Manage');

		$Sender->SetData('Overrides', \Aelia\OverridesManager::Instance()->GetOverrides());
		$Sender->Render($this->GetView('overrideslistpage_view.php'));
	}

	/**
	 * Renders the Settings page.
	 *
	 * @param Gdn_Controller Sender Sending controller instance.
	 */
	public function Controller_Settings($Sender) {
		$Sender->SetData('CurrentPath', AELIAFOUNDATION_PLUGIN_GENERALSETTINGS_URL);
		// Prevent non-admins from accessing this page
		$Sender->Permission('Vanilla.Settings.Manage');

		$Sender->SetData('PluginDescription', $this->GetPluginKey('Description'));

		//$Validation = new Gdn_Validation();
		$ConfigurationModel = new Gdn_ConfigurationModel($Validation);
		//$ConfigurationModel->SetField(array(
		//	'Plugin.Foundation.RenderCondition'		=> 'all',
		//	'Plugin.Foundation.TrimSize'		=> 100
		//));

		// Set the model on the form.
		$Sender->Form->SetModel($ConfigurationModel);

		// If seeing the form for the first time...
		if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
			// Apply the config settings to the form.
			//$Sender->Form->SetData($ConfigurationModel->Data);
		}
		else {
			//$ConfigurationModel->Validation->ApplyRule('Plugin.Foundation.RenderCondition', 'Required');
			//
			//$ConfigurationModel->Validation->ApplyRule('Plugin.Foundation.TrimSize', 'Required');
			//$ConfigurationModel->Validation->ApplyRule('Plugin.Foundation.TrimSize', 'Integer');
			//
			//$Saved = $Sender->Form->Save();
			//if ($Saved) {
			//		$Sender->StatusMessage = T("Your changes have been saved.");
			//}
		}

		// GetView() looks for files inside plugins/PluginFolderName/views/ and returns their full path. Useful!
		$Sender->Render($this->GetView('generalsettings_view.php'));
	}

	/**
	 * Adds a link to the dashboard menu.
	 *
	 * @param $Sender Sending controller instance.
	 */
	public function Base_GetAppSettingsMenuItems_Handler($Sender) {
		$Menu = $Sender->EventArguments['SideMenu'];
		$Menu->AddLink('Add-ons', $this->GetPluginKey('Name'), 'plugin/aeliafoundation', 'Garden.Settings.Manage');
	}

	/**
	 * Handler for Base_BeforeRenderAsset event.
	 * Displays admin and user messages (if any).
	 *
	 * @param $Sender Sending controller instance.
	 */
	public function Base_BeforeRenderAsset_Handler($Sender) {
		// Display Admin messages
		if((GetValue('AssetName', $Sender->EventArguments) == 'Messages') &&
			 Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {
			$Updater = \Aelia\Installer::Factory();
			$Updater->Update(get_class($this), self::VERSION);

			// Display admin messages
			$MessagesModule = new \Aelia\MessagesModule(null, $this->Messages[self::MESSAGE_GROUP_ADMIN]);
			$Sender->AddModule($MessagesModule);
		}

		if(GetValue('AssetName', $Sender->EventArguments) == 'Content') {
			
		}
	}

	/**
	 * Handler of Event SettingsController::AddEditCategory.
	 * Overrides standard Category Add and Edit Views. It fires an extra event
	 * which allows other plugins to alter the form.
	 *
 	 * @param Controller Sender Sending controller instance.
 	 */
	public function SettingsController_AddEditCategory_Handler($Sender) {
		$RequestMethod = $Sender->RequestMethod;
		try {
			$View = $this->GetView('core_overrides/settings/' . $RequestMethod . '.php');

			$Sender->View = $View;
		}
		catch(Exception $e) {
			$this->Log->error(sprintf(T('Event "AddEditCategory" fired by unsupported Request Method "%s"'),
																$RequestMethod));
		}
	}

	/**
	 * Determines if a path is absolute.
	 *
	 * @param string Path The path to inspect.
	 * @return bool True, if the path is absolute, False otherwise.
	 */
	public static function IsAbsolutePath($Path) {
		return (preg_match('/^(\w\:|\\\\|\/|\\\\\\\\).*$/', $Path) == 1);
	}

	/**
	 * Plugin setup
	 * This method is fired only once, immediately after the plugin has been
	 * enabled in the /plugins/ screen.
	 */
	public function Setup() {
		// Create shortcut Route to Award Foundation Classes plugin pages
		Gdn::Router()->SetRoute('^afc(/?.*)$',
														AELIAFOUNDATION_PLUGIN_BASE_URL . '$1',
														'Internal');
	}

	/**
	 * Plugin cleanup on Disable.
	 */
	public function OnDisable() {
		// Remove the Routes created by the Plugin.
		Gdn::Router()->DeleteRoute('^afc(/?.*)$');
	}

	/**
	 * Plugin cleanup on Remove.
	 */
	public function CleanUp() {
	}
}
