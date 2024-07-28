<?php
/**
 *
 * Disable Warn Groups extension for the phpBB Forum Software package
 *
 * @copyright (c) 2024, Kailey Snay, https://www.snayhomelab.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\disablewarngroups\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Disable Warn Groups event listener
 */
class main_listener implements EventSubscriberInterface
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** \phpbb\user */
	protected $user;

	/** @var string */
	protected $table_prefix;

	/**
	 * Constructor
	 *
	 * @param \phpbb\db\driver\driver_interface  $db
	 * @param \phpbb\language\language           $language
	 * @param \phpbb\request\request             $request
	 * @param \phpbb\template\template           $template
	 * @param \phpbb\user                        $user
	 * @param string                             $table_prefix
	 */
	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\language\language $language, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, $table_prefix)
	{
		$this->db = $db;
		$this->language = $language;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->table_prefix = $table_prefix;
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.acp_manage_group_request_data'	=> 'acp_manage_group_request_data',
			'core.acp_manage_group_initialise_data'	=> 'acp_manage_group_initialise_data',
			'core.acp_manage_group_display_form'	=> 'acp_manage_group_display_form',

			'core.mcp_warn_post_before'	=> 'warn_before',
			'core.mcp_warn_user_before'	=> 'warn_before',

			'core.user_setup'	=> 'user_setup',
		];
	}

	public function acp_manage_group_request_data($event)
	{
		$event->update_subarray('submit_ary', 'warn', $this->request->variable('group_warn', 0));
	}

	public function acp_manage_group_initialise_data($event)
	{
		$event->update_subarray('test_variables', 'warn', 'int');
	}

	public function acp_manage_group_display_form($event)
	{
		$this->template->assign_vars([
			'GROUP_WARN'	=> (!empty($event['group_row']['group_warn'])) ? ' checked="checked"' : '',
		]);
	}

	public function warn_before($event)
	{
		$group_warn_list = $this->group_warn_list();

		if ((in_array($event['user_row']['user_id'], $group_warn_list)) && ($this->user->data['user_type'] != USER_FOUNDER))
		{
			trigger_error($this->language->lang('CANNOT_WARN_GROUP'));
		}
	}

	/**
	 * Load common language files
	 */
	public function user_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'phpbbmodders/disablewarngroups',
			'lang_set' => 'common',
		];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	private function group_warn_list()
	{
		$sql_ary = [
			'SELECT'	=> 'g.group_id, g.group_warn, ug.user_id, ug.group_id',

			'FROM'		=> [
				$this->table_prefix . 'groups'	=> 'g',
			],

			'LEFT_JOIN'	=> [
				[
					'FROM'	=> [
						$this->table_prefix . 'user_group'	=> 'ug'
					],
					'ON'	=> 'g.group_id = ug.group_id'
				],
			],

			'WHERE'		=> 'g.group_warn = 0'
		];
		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		$group_warn_list = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$group_warn_list[] = $row['user_id'];
		}
		$this->db->sql_freeresult($result);

		return $group_warn_list;
	}
}
