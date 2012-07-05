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
 * Network node location form
 * @author B. Krastev <bkrastev@web-teh.net>
 */
class Nodes_Form_NodeLocation extends Unwired_Form
{
	public function init()
	{
		parent::init();

		$this->setLegend('nodes_index_edit_form_legend_location');

		$this->setDecorators(array('FormElements',
								   'fieldset' => array('decorator' => 'fieldset',
							           				   'options' => array ('class' => 'span-10 last')),
								   'Form'));

		$this->addElement('text', 'address', array('label' => 'nodes_index_edit_form_address',
													'required' => false,
													'class' => 'span-6',
													'validators' => array('len' => array('validator' => 'StringLength',
																					     'options' => array('min' => 5)))));
		$this->addElement('text', 'city', array('label' => 'nodes_index_edit_form_city',
													'required' => false,
													'class' => 'span-6',
													'validators' => array('len' => array('validator' => 'StringLength',
																					     'options' => array('min' => 3)))));
		$this->addElement('text', 'zip', array('label' => 'nodes_index_edit_form_zip',
													'required' => false,
													'class' => 'span-6',
													'validators' => array('len' => array('validator' => 'Regex',
																					     'options' => array('pattern' => '/^[a-z0-9]+[a-z0-9\s]+$/i',
		                                                                                                    'messages' => array('regexNotMatch' => 'nodes_index_edit_form_zip_error'))))));
		$this->addElement('CountrySelect', 'country', array('label' => 'nodes_index_edit_form_country',
															'required' => true,
															'class' => 'span-6'));

		$this->addElement('text', 'latitude', array('label' => 'nodes_index_edit_form_latitude',
													'required' => false,
													'class' => 'span-5',
													'validators' => array('len' => array('validator' => 'StringLength',
																					     'options' => array('min' => 3)))));

		$this->addElement('text', 'longitude', array('label' => 'nodes_index_edit_form_longitude',
													'required' => false,
													'class' => 'span-5',
													'validators' => array('len' => array('validator' => 'StringLength',
																					     'options' => array('min' => 3)))));
		$this->addDisplayGroup($this->getElements(),
							   'node_location');

	    $this->setDisplayGroupDecorators(array('FormElements',
		   							     	   'HtmlTag' => array('decorator' => 'HtmlTag',
	    														  'options' => array ('tag' => 'div',
													 	     						  'class' => 'span-10 last'))));
	}
}