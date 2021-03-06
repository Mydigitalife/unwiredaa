<?php
/**
* Unwired AA GUI
*
* Author & Copyright (c) 2011 Unwired Networks GmbH
* alexander.szlezak@unwired.at
*
* Licensed under the terms of the Affero Gnu Public License version 3
* (AGPLv3 - http://www.gnu.org/licenses/agpl.html) or our proprietory
* license available at http://www.unwired.at/license.html
*/

/**
 * Network node form
 * @author B. Krastev <bkrastev@web-teh.net>
 */
class Nodes_Form_Node extends Unwired_Form
{
	public function init()
	{
		parent::init();

		$this->addElement('text', 'name', array('label' => 'nodes_index_edit_form_name',
												'order' => 1,
												'class' => 'span-5',
												'required' => true,
												'validators' => array('len' => array('validator' => 'StringLength',
																					 'options' => array('min' => 2)))));
		$this->addElement('text', 'mac', array('label' => 'nodes_index_edit_form_mac',
											   'order' => 2,
												'class' => 'span-5',
												'required' => false,
												'filters' => array('sanitize' => array('filter' => 'PregReplace',
																					   'options' => array('match' => '/[\-:\s]/',
																										  'replace' => ''))),
												'validators' => array('mac',
																	  'db' => array('validator' => 'Db_NoRecordExists',
																				    'options' => array(
																								'table' => 'node',
																						        'field' => 'mac'
																					)))));

		$this->addElement('select', 'status', array('label' => 'nodes_index_edit_form_status',
													'order' => 3,
													'class' => 'span-5',
													'required' => true,
													'multiOptions' => array('disabled' => 'nodes_index_edit_form_status_disabled',
																			'enabled' => 'nodes_index_edit_form_status_enabled',
																			'planning' => 'nodes_index_edit_form_status_planning')));

		$this->addElement('hidden', 'group_id', array('label' => 'nodes_index_edit_form_group',
													  'order' => 4,
											  	 	  'required' => true,
													  'validators' => array('Int')));


		$locationForm = new Nodes_Form_NodeLocation();
		$locationForm->removeDecorator('Form');
		$locationForm->setIsArray(true);
		$locationForm->setOrder(5);

		$settingsForm = new Nodes_Form_NodeSettings();
		$settingsForm->removeDecorator('Form');
		$settingsForm->setIsArray(true);
		$settingsForm->setOrder(6);

		$this->addSubForms(array('settings' => $settingsForm,
								 'location' => $locationForm));

		$this->addDisplayGroup(array('name',
									 'mac',
									 'status',
									 'group_id'),
				 			   'generic');


	    $this->setDisplayGroupDecorators(array('FormElements',
		   							     	   'HtmlTag' => array('decorator' => 'fieldset',
	    														  'options' => array ('class' => 'span-9',
	    																			  'legend' => 'Node'))));


		$settingsForm->addElement('submit', 'form_element_submit', array('label' => 'nodes_index_edit_form_button_save',
	 														 	 'order' => 7,
																 'class'	=> 'button',
															 	 'decorators' => array('ViewHelper',
																				 		array(array('span' => 'HtmlTag'),
						            				   									 	   array ('tag' => 'span',
																		   				 		 	  'class' => 'button green')),
																						)));
		$settingsForm->addElement('href', 'form_element_cancel', array('label' => 'nodes_index_edit_form_button_cancel',
	 														 	 'order' => 8,
																 'href' => (isset($this->getView()->refererUrl)) ?
																					$this->getView()->refererUrl : null,
																 'data' => array(
																				'params' => array('module' => 'nodes',
																					  			  'controller' => 'index',
																					  			  'action' => 'index'),
																				'route' => 'default',
																				'reset' => true
																			),
															 	 'decorators' => array('ViewHelper',
																				 		array(array('span' => 'HtmlTag'),
						            				   									 	   array ('tag' => 'span',
																		   				 		 	  'class' => 'button blue')),
																						)));

	    $settingsForm->addDisplayGroup(array('form_element_submit', 'form_element_cancel'),
							   'formbuttons');

	    $settingsForm->getDisplayGroup('formbuttons')
	    				 ->setDecorators(array('FormElements',
		   							     	   'HtmlTag' => array('decorator' => 'HtmlTag',
	    														  'options' => array ('tag' => 'div',
													 	     						  'class' => 'buttons span-18'))));
	}

	public function populate(array $values)
	{
		if (!isset($values['location'])) {
			$values['location'] = array();
		}
		if (!isset($values['settings'])) {
			$values['settings'] = array();
		}
		return parent::populate($values);
	}

	public function isValid($data)
	{

		if ($data['status'] != 'planning') {
			$this->getElement('mac')->setRequired(true);
		}
		return parent::isValid($data);
	}
}