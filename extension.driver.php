<?php

	/**
	 * @package sections_panel
	 */

	require_once TOOLKIT . '/class.entrymanager.php';
	require_once TOOLKIT . '/class.sectionmanager.php';

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
				'version'		=> '1.0',
				'release-date'	=> '2011-11-18',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://rowanlewis.com/',
					'email'			=> 'me@rowanlewis.com'
				),
				'description'	=> 'Add sections directly to the dashboard, without having to create a separate datasource, or any datasource at all.'
			);
		}

		/**
		 * Subscribe to Dashboard and Symphony delegates.
		 */
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'InitaliseAdminPageHead',
					'callback'	=> 'dashboardAppendAssets'
				),
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

		/**
		 * Append CSS and javaScript assets to the dashboard page.
		 *
		 * @param array $context
		 */
		public function dashboardAppendAssets($context) {
			$page = $context['parent']->Page;

			if ($page instanceof contentExtensionDashboardIndex) {
				$page->addStylesheetToHead(URL . '/extensions/sections_panel/assets/panel.css', 'screen', 666);
				$page->addScriptToHead(URL . '/extensions/sections_panel/assets/panel.js', 667);
			}
		}

		/**
		 * Generate the panel options view.
		 *
		 * @param array $context
		 */
		public function dashboardPanelOptions($context) {
			if ($context['type'] != 'section_to_table') return;

			$config = $context['existing_config'];
			$sm = new SectionManager(Symphony::Engine());
			$section_selected = (
				isset($config['section'])
					? $config['section']
					: null
			);
			$columns_selected = (
				isset($config['columns'])
					? $config['columns']
					: array()
			);

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(
				new XMLElement('legend', __('Section to Table'))
			);

			$label = Widget::Label(__('Section'));
			$select = new XMLElement('select');
			$select->setAttribute('name', 'config[section]');

			// Section options:
			foreach ($sm->fetch() as $section) {
				$option = new XMLElement('option');
				$option->setAttribute('value', $section->get('id'));
				$option->setValue($section->get('name'));

				if ($section_selected == $section->get('id')) {
					$option->setAttribute('selected', 'selected');
				}

				$select->appendChild($option);
			}

			$label->appendChild($select);

			if (isset($context['errors']['section'])) {
				$label = Widget::wrapFormElementWithError($label, $context['errors']['section']);
			}

			$fieldset->appendChild($label);

			$label = Widget::Label(__('Show Columns'));
			$fieldset->appendChild($label);
			$current_context = null;

			// Section contexts:
			foreach ($sm->fetch() as $section) {
				$div = new XMLElement('div');
				$div->setAttribute('data-section-context', $section->get('id'));
				$div->setAttribute('style', 'display: none;');

				$select = new XMLElement('select');
				$select->setAttribute('name', 'config[columns][]');
				$select->setAttribute('multiple', 'multiple');

				if (isset($first_select) === false) {
					$current_context = $div;
				}

				foreach ($section->fetchFields() as $field) {
					$option = new XMLElement('option');
					$option->setAttribute('value', $field->get('id'));
					$option->setValue($field->get('label'));

					if (in_array($field->get('id'), $columns_selected)) {
						$option->setAttribute('selected', 'selected');
						$current_context = $div;
					}

					$select->appendChild($option);
				}

				$div->appendChild($select);
				$fieldset->appendChild($div);
			}

			// Show the first/currently selected section:
			if ($current_context) {
				$current_context->setAttribute('style', 'display: block;');
			}

			$input = Widget::Input(
				'config[entries]',
				(
					isset($config['entries'])
						? $config['entries']
						: 5
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

		/**
		 * Generate the panel view.
		 *
		 * @param array $context
		 */
		public function dashboardPanelRender($context) {
			if ($context['type'] != 'section_to_table') return;

			$config = $context['config'];
			$panel = $context['panel'];
			$em = new EntryManager(Symphony::Engine());
			$sm = new SectionManager(Symphony::Engine());

			// Get section information:
			$section = $sm->fetch($config['section']);
			$fields = $section->fetchFields();
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
				if (!in_array($field->get('id'), $config['columns'])) continue;

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
					if (!in_array($field->get('id'), $config['columns'])) continue;

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

		/**
		 * Let the Dashboard know that our panel type exists.
		 *
		 * @param array $context
		 */
		public function dashboardPanelTypes($context) {
			$context['types']['section_to_table'] = __('Section to Table');
		}

		/**
		 * Generate a list of available sections for use in the Widget::Select function.
		 *
		 * @param array $context
		 */
		public function getSectionOption(Section $section, $selected_id = null) {
			return array(
				$section->get('id'),
				$selected_id == $section->get('id'),
				$section->get('name')
			);
		}
	}

?>