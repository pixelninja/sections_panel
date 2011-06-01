<?php
	
	/**
	 * @package sections_panel
	 */
	
	/**
	 * Add sections directly to the dashboard, skipping the datasource step.
	 */
	class Extension_Sections_Panel extends Extension {
		/**
		 * Extension information.
		 */
		public function about() {
			return array(
				'name'			=> 'Sections Panel',
				'version'		=> '0.1',
				'release-date'	=> '2011-06-01',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://rowanlewis.com/',
					'email'			=> 'me@rowanlewis.com'
				),
				'description'	=> 'Add sections directly to the dashboard, skipping the datasource step.'
			);
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'DashboardPanelOptions',
					'callback'	=> 'dashboardPanelOptions'
				),
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'DashboardPanelRender',
					'callback'	=> 'dashboardPanelRender'
				),
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'DashboardPanelTypes',
					'callback'	=> 'dashboardPanelTypes'
				)
			);
		}
		
		public function dashboardPanelOptions($context) {
			if ($context['type'] != 'section_to_table') return;
			
			$config = $context['existing_config'];
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(
				new XMLElement('legend', __('Section to Table'))
			);
			
			$label = Widget::Label(__('Section'));
			$select = Widget::Select(
				'config[section]',
				$this->getSectionOptions(
					isset($config['section'])
						? $config['section']
						: null
				)
			);
			$label->appendChild($select);
			$fieldset->appendChild($label);
			
			$input = Widget::Input(
				'config[columns]',
				(
					isset($config['columns'])
						? $config['columns']
						: null
				)
			);
			$input->setAttribute('type', 'number');
			$input->setAttribute('size', '3');
			$label = Widget::Label(__(
				'Show the first %s columns in table.',
				array($input->generate())
			));
			$fieldset->appendChild($label);
			
			$input = Widget::Input(
				'config[entries]',
				(
					isset($config['entries'])
						? $config['entries']
						: null
				)
			);
			$input->setAttribute('type', 'number');
			$input->setAttribute('size', '3');
			$label = Widget::Label(__(
				'Show the first %s entries in table.',
				array($input->generate())
			));
			$fieldset->appendChild($label);
			
			$context['form'] = $fieldset;
		}
		
		public function dashboardPanelRender($context) {
			if ($context['type'] != 'section_to_table') return;
			
			$config = $context['config'];
			$panel = $context['panel'];
			$em = new EntryManager(Symphony::Engine());
			$sm = new SectionManager(Symphony::Engine());
			
			// Get section information:
			$section = $sm->fetch($config['section']);
			$fields = $section->fetchVisibleColumns();
			$fields = array_splice(
				$fields, 0,
				(
					isset($config['columns'])
						? $config['columns']
						: 4
				)
			);
			$section_url = sprintf(
				'%s/publish/%s/',
				SYMPHONY_URL, $section->get('handle')
			);
			
			// Get entry information:
			$entries = $em->fetchByPage(1, $section->get('id'), 
				(
					isset($config['entries'])
						? $config['entries']
						: 4
				)
			);
			
			// Build table:
			$table = new XMLElement('table');
			$table->setAttribute('class', 'skinny');
			$table_head = new XMLElement('thead');
			$table->appendChild($table_head);
			$table_body = new XMLElement('tbody');
			$table->appendChild($table_body);
			$panel->appendChild($table);
			
			// Add table headers:
			$row = new XMLElement('tr');
			$table_head->appendChild($row);
			
			foreach ($fields as $field) {
				$cell = new XMLElement('th');
				$cell->setValue($field->get('label'));
				$row->appendChild($cell);
			}
			
			// Add table body:
			foreach ($entries['records'] as $entry) {
				$row = new XMLElement('tr');
				$table_body->appendChild($row);
				$entry_url = $section_url . 'edit/' . $entry->get('id') . '/';
				
				foreach ($fields as $position => $field) {
					$data = $entry->getData($field->get('id'));
					$cell = new XMLElement('td');
					$row->appendChild($cell);
					
					$link = (
						$position === 0
							? Widget::Anchor(__('None'), $entry_url, $entry->get('id'), 'content')
							: null
					);
					$value = $field->prepareTableValue($data, $link, $entry->get('id'));
					
					if (isset($link)) {
						$value = $link->generate();
					}
					
					if ($value == 'None' || strlen($value) === 0) {
						$cell->setAttribute('class', 'inactive');
						$cell->setValue(__('None'));
					}
					
					else {
						$cell->setValue($value);
					}
				}
			}
		}
		
		public function dashboardPanelTypes($context) {
			$context['types']['section_to_table'] = __('Section to Table');
		}
		
		public function getSectionOptions($selected_id = null) {
			$sm = new SectionManager(Symphony::Engine());
			$options = array();
			
			foreach ($sm->fetch() as $section) {
				$options[] = array(
					$section->get('id'),
					$selected_id == $section->get('id'),
					$section->get('name')
				);
			}
			
			return $options;
		}
	}
	
?>