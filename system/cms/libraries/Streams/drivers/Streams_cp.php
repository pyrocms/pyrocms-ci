<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Control Panel Driver
 *
 * Contains functions that allow for
 * constructing PyrCMS stream control
 * panel elements.
 *
 * @author  	Parse19
 * @package  	PyroCMS\Core\Libraries\Streams\Drivers
 */ 
 
class Streams_cp extends CI_Driver {

	private $CI;

	// --------------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->CI = get_instance();
	}

	// --------------------------------------------------------------------------

	/**
	 * Entries Table
	 *
	 * Creates a table of entries.
 	 *
	 * @param	string - the stream slug
	 * @param	string - the stream namespace slug
	 * @param	[mixed - pagination, either null for no pagination or a number for per page]
	 * @param	[null - pagination uri without offset]
	 * @param	[bool - setting this to true will take care of the $this->template business
	 * @param	[array - extra params (see below)]
	 * @return	mixed - void or string
	 *
	 * Extra parameters to pass in $extra array:
	 *
	 * title	- Title of the page header (if using view override)
	 *			$extra['title'] = 'Streams Sample';
	 * 
	 * buttons	- an array of buttons (if using view override)
	 *			$extra['buttons'] = array(
	 *				'label' 	=> 'Delete',
	 *				'url'		=> 'admin/streams_sample/delete/-entry_id-',
	 *				'confirm'	= true
	 *			);
	 * columns  - an array of field slugs to display. This overrides view options.
	 * 			$extra['columns'] = array('field_one', 'field_two');
	 *
 	 * sorting  - bool. Whether or not to turn on the drag/drop sorting of entries. This defaults
 	 * 			to the sorting option of the stream.
	 *
	 * see docs for more explanation
	 */
	public function entries_table($stream_slug, $namespace_slug, $pagination = null, $pagination_uri = null, $view_override = false, $extra = array())
	{
		// Get stream
		$stream = $this->stream_obj($stream_slug, $namespace_slug);
		if ( ! $stream) $this->log_error('invalid_stream', 'entries_table');

 		// -------------------------------------
		// Get Header Fields
		// -------------------------------------
		
 		$stream_fields = $this->CI->streams_m->get_stream_fields($stream->id);

 		// We need to make sure that stream_fields is 
 		// at least an empty object.
 		if ( ! is_object($stream_fields))
 		{
 			$stream_fields = new stdClass;
 		}

 		$stream_fields->id = new stdClass;
  		$stream_fields->created = new stdClass;
 		$stream_fields->updated = new stdClass;
 		$stream_fields->created_by = new stdClass;

  		$stream_fields->id->field_name 				= lang('streams:id');
		$stream_fields->created->field_name 		= lang('streams:created_date');
 		$stream_fields->updated->field_name 		= lang('streams:updated_date');
 		$stream_fields->created_by->field_name 		= lang('streams:created_by');

 		// -------------------------------------
		// Find offset URI from array
		// -------------------------------------
		
		if (is_numeric($pagination))
		{
			$segs = explode('/', $pagination_uri);
			$offset_uri = count($segs)+1;
	
	 		$offset = $this->CI->uri->segment($offset_uri, 0);

			// Calculate actual offset if not first page
			if ( $offset > 0 )
			{
				$offset = ($offset - 1) * $pagination;
			}
  		}
  		else
  		{
  			$offset_uri = null;
  			$offset = 0;
  		}

  		// -------------------------------------
		// Sorting
		// @since 2.1.5
		// -------------------------------------

		if ($stream->sorting == 'custom' or (isset($extra['sorting']) and $extra['sorting'] === true))
		{
			$stream->sorting = 'custom';

			// As an added measure of obsurity, we are going to encrypt the
			// slug of the module so it isn't easily changed.
			$this->CI->load->library('encrypt');

			// We need some variables to use in the sort.
			$this->CI->template->append_metadata('<script type="text/javascript" language="javascript">var stream_id='.$stream->id.'; var stream_offset='.$offset.'; var streams_module="'.$this->CI->encrypt->encode($this->CI->module_details['slug']).'";
				</script>');
			$this->CI->template->append_js('streams/entry_sorting.js');
		}
  
  		$data = array(
  			'stream'		=> $stream,
  			'stream_fields'	=> $stream_fields,
  			'buttons'		=> isset($extra['buttons']) ? $extra['buttons'] : null,
  			'filters'		=> isset($extra['filters']) ? $extra['filters'] : null,
  			'search_id'		=> isset($_COOKIE['streams_core_filters']) ? $_COOKIE['streams_core_filters'] : null,
  		);
 
  		// -------------------------------------
		// Columns
		// @since 2.1.5
		// -------------------------------------

		if (isset($extra['columns']) and is_array($extra['columns']))
		{
			$stream->view_options = $extra['columns'];
		}

 		// -------------------------------------
		// Set / Expire Filtering
		// -------------------------------------

		if (isset($_POST['filter']))
		{
			// We don't need this
			unset($_POST['filter']);

			// So we can find it later
			$search_id = md5(rand().microtime());

			// Save the search terms and some info
			$this->CI->db->insert('data_stream_searches', array('stream_slug' => $stream->stream_slug, 'stream_namespace' => $stream->stream_namespace, 'search_id' => $search_id, 'search_term' => serialize($_POST), 'ip_address' => $_SERVER['REMOTE_ADDR'], 'total_results' => 0));

			// Set dah cookie
			setcookie('streams_core_filters', $search_id, time() + 86400, '/', '.'.SITE_DOMAIN);

			// Set filter_data
			$data['filter_data'] = array(
				'filters' => $_POST,
				'stream' => $stream->stream_slug,
				);
		}

		// Clear old ones?
		elseif ( isset($_POST['clear_filters']) )
		{
			setcookie('streams_core_filters', '', time() - 9999, '/', '.'.SITE_DOMAIN);
		}

		// Must be an existing one..
		elseif ( isset($_COOKIE['streams_core_filters']) )
		{

			// Get the database search record
			$db_search = $this->CI->db->select('search_term, stream_slug, stream_namespace')->where('search_id', $data['search_id'])->limit(1)->get('data_stream_searches')->row(0);

			// Is this the right search module / namespace?
			if ( $db_search->stream_slug == $stream->stream_slug and $db_search->stream_namespace == $stream->stream_namespace )
			{

				// Add it
				$data['filter_data'] = array(
					'filters' => unserialize($db_search->search_term),
					'stream' => $db_search->stream_slug,
					'namespace' => $db_search->stream_namespace,
					);
			}
			else
			{
				setcookie('streams_core_filters', '', time() - 9999, '/', '.'.SITE_DOMAIN);
			}
			
		}
		else
		{
			setcookie('streams_core_filters', '', time() - 9999, '/', '.'.SITE_DOMAIN);
		}

		$filter_data = isset($data['filter_data']) ? $data['filter_data'] : null;

 		// -------------------------------------
		// Get Entries
		// -------------------------------------
		
		$limit = ($pagination) ? $pagination : null;
	
		$data['entries'] = $this->CI->streams_m->get_stream_data(
														$stream,
														$stream_fields, 
														$limit,
														$offset,
														$filter_data);


		// -------------------------------------
		// Pagination
		// -------------------------------------

		if ( $filter_data != null )
		{

			// Loop through and apply the filters
			foreach ( $filter_data['filters'] as $filter=>$value )
			{
				if ( !empty($value) ) $this->CI->db->like(str_replace('f_', '', $filter), $value);
			}
		}

		$data['pagination'] = create_pagination(
									$pagination_uri,
									$this->CI->db->select('id')->count_all_results($stream->stream_prefix.$stream->stream_slug),
									$pagination,
									$offset_uri
								);
		
		// -------------------------------------
		// Build Pages
		// -------------------------------------
		
		// Set title
		if (isset($extra['title']))
		{
			$this->CI->template->title($extra['title']);
		}

		// Set custom no data message
		if (isset($extra['no_entries_message']))
		{
			$data['no_entries_message'] = $extra['no_entries_message'];
		}
		
		$table = $this->CI->load->view('admin/partials/streams/entries', $data, true);
		
		if ($view_override)
		{
			// Hooray, we are building the template ourself.
			$this->CI->template->build('admin/partials/blank_section', array('content' => $table));
		}
		else
		{
			// Otherwise, we are returning the table
			return $table;
		}
	}

	// --------------------------------------------------------------------------

	/**
	 * Entry Form
	 *
	 * Creates an entry form for a stream.
	 *
	 * @param	string - stream slug
	 * @param	string - stream namespace
	 * @param	mode - new or edit
	 * @param	[array - current entry data]
	 * @param	[bool - view override - setting this to true will build template]
	 * @param	[array - extra params (see below)]
	 * @param	[array - fields to skip]	 
	 * @return	mixed - void or string
	 *
	 * Extra parameters to pass in $extra array:
	 *
	 * email_notifications 	- see docs for more explanation
	 * return				- URL to return to after submission
	 * 							defaults to current URL.
	 * success_message		- Flash message to show after successful submission
	 * 							defaults to generic successful entry submission message
	 * failure_message		- Flash message to show after failed submission,
	 * 							defaults to generic failed entry submission message
	 * required				- String to show as required - this defaults to the
	 * 							standard * for the PyroCMS CP
	 * title				- Title of the form header (if using view override)
	 * no_fields_message    - Custom message when there are no fields.
	 */
	public function entry_form($stream_slug, $namespace_slug, $mode = 'new', $entry_id = null, $view_override = false, $extra = array(), $skips = array(), $tabs = false, $hidden = array(), $defaults = array())
	{
		$stream = $this->stream_obj($stream_slug, $namespace_slug);
		if ( ! $stream) $this->log_error('invalid_stream', 'form');

		// Load up things we'll need for the form
		$this->CI->load->library(array('form_validation', 'streams_core/Fields'));
	
		if ($mode == 'edit')
		{
			if( ! $entry = $this->CI->row_m->get_row($entry_id, $stream, false))
			{
				$this->log_error('invalid_row', 'form');
			}
		}
		else
		{
			$entry = null;
		}

		// Get our field form elements.
		$fields = $this->CI->fields->build_form($stream, $mode, $entry, false, false, $skips, $extra, $defaults);

		$data = array(
					'fields' 	=> $fields,
					'tabs'		=> $tabs,
					'hidden'	=> $hidden,
					'defaults'	=> $defaults,
					'stream'	=> $stream,
					'entry'		=> $entry,
					'mode'		=> $mode);
		
		// Set title
		if (isset($extra['title']))
		{
			$this->CI->template->title($extra['title']);
		}
		// Set return uri
		if (isset($extra['return']))
		{
			$data['return'] = $extra['return'];
		}

		// Set the no fields mesage. This has a lang default.
		if (isset($extra['no_fields_message']))
		{
			$data['no_fields_message'] = $extra['no_fields_message'];
		}
		
		$this->CI->template->append_js('streams/entry_form.js');
		
		if ($data['tabs'] === false)
		{
			$form = $this->CI->load->view('admin/partials/streams/form', $data, true);
		}
		else
		{
			// Make the fields keys the input_slug. This will make it easier to build tabs. Less looping.
			foreach ( $data['fields'] as $k => $v ){
				$data['fields'][$v['input_slug']] = $v;
				unset($data['fields'][$k]);
			}

			$form = $this->CI->load->view('admin/partials/streams/tabbed_form', $data, true);
		}
		
		if ($view_override === false) return $form;
		
		$data['content'] = $form;
		//$this->CI->data->content = $form;

		$this->CI->data = new stdClass;
		$this->CI->data->content = $form;
		
		$this->CI->template->build('admin/partials/blank_section', $data);
	}

	// --------------------------------------------------------------------------

	/**
	 * Custom Field Form
	 *
	 * Creates a custom field form.
	 *
	 * This allows you to easily create a form that users can
	 * use to add new fields to a stream. This functions as the
	 * form assignment as well.
	 *
	 * @param	string - stream slug
	 * @param	string - namespace
	 * @param 	string - method - new or edit. defaults to new
	 * @param 	string - uri to return to after success/fail
	 * @param 	[int - the assignment id if we are editing]
	 * @param	[array - field types to include]
	 * @param	[bool - view override - setting this to true will build template]
	 * @param	[array - extra params (see below)]
	 * @return	mixed - void or string
	 *
	 * Extra parameters to pass in $extra array:
	 *
	 * title	- Title of the form header (if using view override)
	 *			$extra['title'] = 'Streams Sample';
	 * 
	 * show_cancel - bool. Show the cancel button or not?
	 * cancel_url - url or uri to link to for cancel button
	 *
	 * see docs for more.
	 */
	public function field_form($stream_slug, $namespace, $method = 'new', $return, $assign_id = null, $include_types = array(), $view_override = false, $extra = array(), $exclude_types = array(), $skips = array())
	{
		$data = array();
		$data['field'] = new stdClass;
		
		// We always need our stream
		$stream = $this->stream_obj($stream_slug, $namespace);
		if ( ! $stream) $this->log_error('invalid_stream', 'form');

		// -------------------------------------
		// Include/Exclude Field Types
		// -------------------------------------
		// Allows the inclusion or exclusion of
		// field types.
		// -------------------------------------

		if ($include_types)
		{
			$ft_types = new stdClass();

			foreach ($this->CI->type->types as $type)
			{
				if (in_array($type->field_type_slug, $include_types))
				{
					$ft_types->{$type->field_type_slug} = $type;
				}
			}
		}
		elseif (count($exclude_types) > 0)
		{
			$ft_types = new stdClass();

			foreach ($this->CI->type->types as $type)
			{
				if ( ! in_array($type->field_type_slug, $exclude_types))
				{
					$ft_types->{$type->field_type_slug} = $type;
				}
			}
		}
		else
		{
			$ft_types = $this->CI->type->types;
		}

		// -------------------------------------
		// Field Type Assets
		// -------------------------------------
		// These are assets field types may
		// need when adding/editing fields
		// -------------------------------------
   		
   		$this->CI->type->load_field_crud_assets($ft_types);
   		
   		// -------------------------------------
        
		// Need this for the view
		$data['method'] = $method;

		// Get our list of available fields
		$data['field_types'] = $this->CI->type->field_types_array($ft_types);

		// -------------------------------------
		// Get the field if we have the assignment
		// -------------------------------------
		// We'll always work off the assignment.
		// -------------------------------------

		if ($method == 'edit' and is_numeric($assign_id))
		{
			$assignment = $this->CI->db->limit(1)->where('id', $assign_id)->get(ASSIGN_TABLE)->row();

			// If we have no assignment, we can't continue
			if ( ! $assignment) show_error('Could not find assignment');

			// Find the field now
			$data['current_field'] = $this->CI->fields_m->get_field($assignment->field_id);

			// We also must have a field if we're editing
			if ( ! $data['current_field']) show_error('Could not find field.');
		}
		elseif ($method == 'new' and $_POST and $this->CI->input->post('field_type'))
		{
			$data['current_field'] = new stdClass();
			$data['current_field']->field_type = $this->CI->input->post('field_type');
		}
		else
		{
			$data['current_field'] = null;
		}

		// -------------------------------------
		// Cancel Button
		// -------------------------------------

		$data['show_cancel'] = (isset($extra['show_cancel']) and $extra['show_cancel']) ? true : false;
		$data['cancel_uri'] = (isset($extra['cancel_uri'])) ? $extra['cancel_uri'] : null;

		// -------------------------------------
		// Validation & Setup
		// -------------------------------------

		if ($method == 'new')
		{
			$this->CI->fields_m->fields_validation[1]['rules'] .= '|streams_unique_field_slug[new:'.$namespace.']';

			$this->CI->fields_m->fields_validation[1]['rules'] .= '|streams_col_safe[new:'.$stream->stream_prefix.$stream->stream_slug.']';
		}
		else
		{
			// @todo edit version of this.
			$this->CI->fields_m->fields_validation[1]['rules'] .= '|streams_unique_field_slug['.$data['current_field']->field_slug.':'.$namespace.']';

			$this->CI->fields_m->fields_validation[1]['rules'] .= '|streams_col_safe[edit:'.$stream->stream_prefix.$stream->stream_slug.':'.$data['current_field']->field_slug.']';
		}

		$assign_validation = array(
			array(
				'field'	=> 'is_required',
				'label' => 'Is Required', // @todo languagize
				'rules'	=> 'trim'
			),
			array(
				'field'	=> 'is_unique',
				'label' => 'Is Unique', // @todo languagize
				'rules'	=> 'trim'
			),
			array(
				'field'	=> 'instructions',
				'label' => 'Instructions', // @todo languageize
				'rules'	=> 'trim'
			)
		);

		// Get all of our valiation into one super validation object
		$validation = array_merge($this->CI->fields_m->fields_validation, $assign_validation);

		// Check if $skips is set to bypass validation for specified field slugs

		// No point skipping field_name & field_type
		$disallowed_skips = array('field_name', 'field_type');

		if (count($skips) > 0)
		{
			foreach ($skips as $skip)
			{
				// First check if the current skip is disallowed
				if (in_array($skip['slug'], $disallowed_skips))
				{
					continue;
				}

				foreach ($validation as $key => $value) 
				{
					if (in_array($value['field'], $skip))
					{
						unset($validation[$key]);
					}
				}
			}
		}

		$this->CI->form_validation->set_rules($validation);

		// -------------------------------------
		// Process Data
		// -------------------------------------

		if ($this->CI->form_validation->run())
		{

			$post_data = $this->CI->input->post();

			// Set custom data from $skips param

			if (count($skips) > 0)
			{	
				foreach ($skips as $skip)
				{
					if ($skip['slug'] == 'field_slug' && ( ! isset($skip['value']) || empty($skip['value'])))	
					{
						show_error('Set a default value for field_slug in your $skips param.');
					}
					else
					{
						$post_data[$skip['slug']] = $skip['value'];
					}
				}
			}

			if ($method == 'new')
			{
				if ( ! $this->CI->fields_m->insert_field(
									$post_data['field_name'],
									$post_data['field_slug'],
									$post_data['field_type'],
									$namespace,
									$post_data
					))
				{
				
					$this->CI->session->set_flashdata('notice', lang('streams:save_field_error'));	
				}
				else
				{
					// Add the assignment
					if( ! $this->CI->streams_m->add_field_to_stream($this->CI->db->insert_id(), $stream->id, $post_data))
					{
						$this->CI->session->set_flashdata('notice', lang('streams:save_field_error'));	
					}
					else
					{
						$this->CI->session->set_flashdata('success', (isset($extra['success_message']) ? $extra['success_message'] : lang('streams:field_add_success')));	
					}
				}
			}
			else
			{
				if ( ! $this->CI->fields_m->update_field(
									$data['current_field'],
									array_merge($post_data, array('field_namespace' => $namespace))
					))
				{
				
					$this->CI->session->set_flashdata('notice', lang('streams:save_field_error'));	
				}
				else
				{
					// Add the assignment
					if( ! $this->CI->fields_m->edit_assignment(
										$assign_id,
										$stream,
										$data['current_field'],
										$post_data
									))
					{
						$this->CI->session->set_flashdata('notice', lang('streams:save_field_error'));	
					}
					else
					{
						$this->CI->session->set_flashdata('success', (isset($extra['success_message']) ? $extra['success_message'] : lang('streams:field_update_success')));
					}
				}

			}
	
			redirect($return);
		}

		// -------------------------------------
		// See if we need our param fields
		// -------------------------------------
		
		if ($this->CI->input->post('field_type') or $method == 'edit')
		{
			// Figure out where this is coming from - post or data
			if ($this->CI->input->post('field_type'))
			{
				$field_type = $this->CI->input->post('field_type');
			}
			else
			{
				$field_type = $data['current_field']->field_type;
			}
		
			if (isset($this->CI->type->types->{$field_type}))
			{
				// Get the type so we can use the custom params
				$data['current_type'] = $this->CI->type->types->{$field_type};

				if ( ! is_object($data['current_field']))
				{
					$data['current_field'] = new stdClass();
					$data['current_field']->field_data = array();
				}
				
				// Get our standard params
				require_once(PYROSTEAMS_DIR.'libraries/Parameter_fields.php');
				
				$data['parameters'] = new Parameter_fields();
				
				if (isset($data['current_type']->custom_parameters) and is_array($data['current_type']->custom_parameters))
				{
					// Build items out of post data
					foreach ($data['current_type']->custom_parameters as $param)
					{
						if ( ! isset($_POST[$param]) and $method == 'edit')
						{
							if (isset($data['current_field']->field_data[$param]))
							{
								$data['current_field']->field_data[$param] = $data['current_field']->field_data[$param];
							}
						}
						else
						{
							$data['current_field']->field_data[$param] = $this->CI->input->post($param);
						}
					}
				}
			}
		}

		// -------------------------------------
		// Set our data for the form	
		// -------------------------------------

		foreach ($validation as $field)
		{
			if ( ! isset($_POST[$field['field']]) and $method == 'edit')
			{
				// We don't know where the value is. Hooray
				if (isset($data['current_field']->{$field['field']}))
				{
					$data['field']->{$field['field']} = $data['current_field']->{$field['field']};
				}
				else
				{
					$data['field']->{$field['field']} = $assignment->{$field['field']};
				}
			}
			else
			{
				$data['field']->{$field['field']} = $this->CI->input->post($field['field']);
			}
		}

		// -------------------------------------
		// Run field setup events
		// -------------------------------------

		$this->CI->fields->run_field_setup_events($stream, $method, $data['current_field']);

		// -------------------------------------
		// Build page
		// -------------------------------------

		$this->CI->template->append_js('streams/fields.js');

		// Set title
		if (isset($extra['title']))
		{
			$this->CI->template->title($extra['title']);
		}

		$table = $this->CI->load->view('admin/partials/streams/field_form', $data, true);
		
		if ($view_override)
		{
			// Hooray, we are building the template ourself.
			$this->CI->template->build('admin/partials/blank_section', array('content' => $table));
		}
		else
		{
			// Otherwise, we are returning the table
			return $table;
		}
	}

	// --------------------------------------------------------------------------

	/**
	 * Fields Table
	 *
	 * Easily create a table of fields in a certain namespace
	 *
	 * @param	string - the stream slug
	 * @param	string - the stream namespace slug
	 * @param	[mixed - pagination, either null for no pagination or a number for per page]
	 * @param	[null - pagination uri without offset]
	 * @param	[bool - setting this to true will take care of the $this->template business
	 * @param	[array - extra params (see below)]
	 * @param	[array - fields to skip]
	 *
	 * Extra parameters to pass in $extra array:
	 *
	 * title	- Title of the page header (if using view override)
	 *			$extra['title'] = 'Streams Sample';
	 * 
	 * buttons	- an array of buttons (if using view override)
	 *			$extra['buttons'] = array(
	 *				'label' 	=> 'Delete',
	 *				'url'		=> 'admin/streams_sample/delete/-entry_id-',
	 *				'confirm'	= true
	 *			);
	 *
	 * see docs for more explanation
	 */
	public function fields_table($namespace, $pagination = null, $pagination_uri = null, $view_override = false, $extra = array(), $skips = array())
	{	
		$data['buttons'] = isset($extra['buttons']) ? $extra['buttons'] : null;

		// Determine the offset and the pagination URI.
		if (is_numeric($pagination))
		{
			$segs = explode('/', $pagination_uri);
			$page_uri = count($segs)+1;
	
	 		$offset = $this->CI->uri->segment($page_uri, 0);

			// Calculate actual offset if not first page
			if ( $offset > 0 )
			{
				$offset = ($offset - 1) * $pagination;
			}
  		}
  		else
  		{
  			$page_uri = null;
  			$offset = 0;
  		}

		// -------------------------------------
		// Get fields
		// -------------------------------------

		if (is_numeric($pagination))
		{	
			$data['fields'] = $this->CI->fields_m->get_fields($namespace, $pagination, $offset, $skips);
		}
		else
		{
			$data['fields'] = $this->CI->fields_m->get_fields($namespace, false, 0, $skips);
		}

		// -------------------------------------
		// Pagination
		// -------------------------------------

		if (is_numeric($pagination))
		{	
			$data['pagination'] = create_pagination(
											$pagination_uri,
											$this->CI->fields_m->count_fields($namespace),
											$pagination, // Limit per page
											$page_uri // URI segment
										);
		}
		else
		{ 
			$data['pagination'] = null;
		}

		// Allow view to inherit custom 'Add Field' uri
		$data['add_uri'] = isset($extra['add_uri']) ? $extra['add_uri'] : null;
		
		// -------------------------------------
		// Build Pages
		// -------------------------------------

		// Set title
		if (isset($extra['title']))
		{
			$this->CI->template->title($extra['title']);
		}

		$table = $this->CI->load->view('admin/partials/streams/fields', $data, true);
		
		if ($view_override)
		{
			// Hooray, we are building the template ourself.
			$this->CI->template->build('admin/partials/blank_section', array('content' => $table));
		}
		else
		{
			// Otherwise, we are returning the table
			return $table;
		}
	}

	// --------------------------------------------------------------------------

	/**
	 * Field Assignments Table
	 *
	 * Easily create a table of fields in a certain namespace
	 *
	 * @param	string - the stream slug
	 * @param	string - the stream namespace slug
	 * @param	[mixed - pagination, either null for no pagination or a number for per page]
	 * @param	[null - pagination uri without offset]
	 * @param	[bool - setting this to true will take care of the $this->template business
	 * @param	[array - extra params (see below)]
	 * @param	[array - fields to skip]
	 *
	 * Extra parameters to pass in $extra array:
	 *
	 * title	- Title of the page header (if using view override)
	 *			$extra['title'] = 'Streams Sample';
	 * 
	 * buttons	- an array of buttons (if using view override)
	 *			$extra['buttons'] = array(
	 *				'label' 	=> 'Delete',
	 *				'url'		=> 'admin/streams_sample/delete/-entry_id-',
	 *				'confirm'	= true
	 *			);
	 *
	 * see docs for more explanation
	 */
	public function assignments_table($stream_slug, $namespace, $pagination = null, $pagination_uri = null, $view_override = false, $extra = array(), $skips = array())
	{
		$data['buttons'] = (isset($extra['buttons']) and is_array($extra['buttons'])) ? $extra['buttons'] : array();

		// Get stream
		$stream = $this->stream_obj($stream_slug, $namespace);
		if ( ! $stream) $this->log_error('invalid_stream', 'assignments_table');

		if (is_numeric($pagination))
		{
			$segs = explode('/', $pagination_uri);
			$offset_uri = count($segs)+1;

	 		$offset = $pagination*($this->CI->uri->segment($offset_uri, 0)-1);

	 		// Negative value check
	 		if ($offset < 0) $offset = 0;
  		}
		else
		{
			$offset_uri = null;
			$offset = 0;
			$offset_uri = null;
		}

		// -------------------------------------
		// Get assignments
		// -------------------------------------

		if (is_numeric($pagination))
		{	
			$data['assignments'] = $this->CI->streams_m->get_stream_fields($stream->id, $pagination, $offset, $skips);
		}
		else
		{
			$data['assignments'] = $this->CI->streams_m->get_stream_fields($stream->id, null, 0, $skips);
		}

		// -------------------------------------
		// Get number of fields total
		// -------------------------------------
		
		$data['total_existing_fields'] = $this->CI->fields_m->count_fields($namespace);

		// -------------------------------------
		// Pagination
		// -------------------------------------

		if (is_numeric($pagination))
		{	
			$data['pagination'] = create_pagination(
											$pagination_uri,
											$this->CI->fields_m->count_fields($namespace),
											$pagination,
											$offset_uri
										);
		}
		else
		{ 
			$data['pagination'] = false;
		}

		// Allow view to inherit custom 'Add Field' uri
		$data['add_uri'] = isset($extra['add_uri']) ? $extra['add_uri'] : null;

		// -------------------------------------
		// Build Pages
		// -------------------------------------

		// Set title
		if (isset($extra['title']))
		{
			$this->CI->template->title($extra['title']);
		}

		// Set no assignments message
		if (isset($extra['no_assignments_message']))
		{
			$data['no_assignments_message'] = $extra['no_assignments_message'];
		}
		
		$this->CI->template->append_metadata('<script>var fields_offset='.$offset.';</script>');
		$this->CI->template->append_js('streams/assignments.js');

		$table = $this->CI->load->view('admin/partials/streams/assignments', $data, true);
		
		if ($view_override)
		{
			// Hooray, we are building the template ourself.
			$this->CI->template->build('admin/partials/blank_section', array('content' => $table));
		}
		else
		{
			// Otherwise, we are returning the table
			return $table;
		}
	}

	// --------------------------------------------------------------------------

	/**
	 * Tear down assignment + field combo
	 *
	 * Usually we'd just delete the assignment,
	 * but we need to delete the field as well since
	 * there is a 1-1 relationship here.
	 *
	 * @param 	int - assignment id
	 * @param 	bool - force delete field, even if it is shared with multiple streams
	 * @return 	bool - success/fail
	 */
	public function teardown_assignment_field($assign_id, $force_delete = false)
	{
		// Get the assignment
		$assignment = $this->CI->db->limit(1)->where('id', $assign_id)->get(ASSIGN_TABLE)->row();

		if ( ! $assignment)
		{
			$this->log_error('invalid_assignment', 'teardown_assignment_field');
		}
		
		// Get stream
		$stream = $this->CI->streams_m->get_stream($assignment->stream_id);

		// Get field
		$field = $this->CI->fields_m->get_field($assignment->field_id);

		// Delete the assignment
		if ( ! $this->CI->streams_m->remove_field_assignment($assignment, $field, $stream))
		{
			$this->log_error('invalid_assignment', 'teardown_assignment_field');
		}
		
		// Remove the field only if unlocked and assigned once
		if ($field->is_locked == 'no' or $this->CI->fields_m->count_assignments($assignment->field_id) == 1 or $force_delete)
		{
			// Remove the field
			return $this->CI->fields_m->delete_field($field->id);
		}
	}

}
